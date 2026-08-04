-- ============================================================================
-- POS payment features — raw SQL matching Laravel migrations
-- Target: PostgreSQL (DB_CONNECTION=pgsql), schema `rssys` (DB_SCHEMA=rssys)
--
-- Mirrors, in order, the schema changes made by:
--   2026_07_15_000000_add_play_hours_fields_to_ordlne_ph_table
--   2026_07_16_000000_add_payment_fields_to_ordlne_ph_table
--   2026_07_31_000000_create_orlne_pay_table
--   2026_07_31_000001_create_charge_accounts_table
--   2026_08_03_000000_add_ord_code_ph_to_orlne_pay_table
--   2026_08_03_000001_add_payment_fields_to_ordhdr_table
--
-- Also patches `orhdr` (the legacy Official Receipt table — not owned by any
-- migration here) with the `pay_code2`/`payment2`/`amnt_tendered2` "slot 2"
-- columns that `PaymentsController::recordOfficialReceipt()` and the Cashier
-- Report write/read.
--
-- Fully idempotent — every ADD COLUMN and ADD CONSTRAINT is guarded, so this
-- is safe to run start-to-finish on a database that already has some or all
-- of it applied. Confirmed against live_mimo@72.60.233.51 on 2026-08-04:
-- all 6 migrations + orlne_pay's FKs were already present there, only
-- orhdr.payment2 was actually missing — re-running the earlier non-idempotent
-- version of this script would have failed on "constraint already exists"
-- and rolled back before ever reaching the orhdr fix.
-- ============================================================================

BEGIN;

-- 2026_07_15_000000_add_play_hours_fields_to_ordlne_ph_table
ALTER TABLE rssys.ordlne_ph ADD COLUMN IF NOT EXISTS others_amnt NUMERIC(10, 2) NOT NULL DEFAULT 0;
ALTER TABLE rssys.ordlne_ph ADD COLUMN IF NOT EXISTS disc_amnt   NUMERIC(10, 2) NOT NULL DEFAULT 0;

-- 2026_07_16_000000_add_payment_fields_to_ordlne_ph_table
ALTER TABLE rssys.ordlne_ph ADD COLUMN IF NOT EXISTS cash_tendered NUMERIC(10, 2) NULL;
ALTER TABLE rssys.ordlne_ph ADD COLUMN IF NOT EXISTS change_amnt   NUMERIC(10, 2) NULL;
ALTER TABLE rssys.ordlne_ph ADD COLUMN IF NOT EXISTS is_paid       BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE rssys.ordlne_ph ADD COLUMN IF NOT EXISTS paid_at       TIMESTAMP(0) WITHOUT TIME ZONE NULL;

-- 2026_07_31_000000_create_orlne_pay_table
CREATE TABLE IF NOT EXISTS rssys.orlne_pay (
    id             BIGSERIAL PRIMARY KEY,
    ordlne_ph_id   BIGINT NOT NULL,
    payment_method VARCHAR(20) NOT NULL,
    amount         NUMERIC(10, 2) NOT NULL,
    cash_tendered  NUMERIC(10, 2) NULL,
    change_amnt    NUMERIC(10, 2) NULL,
    paid_at        TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at     TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at     TIMESTAMP(0) WITHOUT TIME ZONE NULL
);

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'orlne_pay_ordlne_ph_id_foreign') THEN
        ALTER TABLE rssys.orlne_pay
            ADD CONSTRAINT orlne_pay_ordlne_ph_id_foreign FOREIGN KEY (ordlne_ph_id)
                REFERENCES rssys.ordlne_ph (id) ON DELETE CASCADE;
    END IF;
END $$;

-- 2026_07_31_000001_create_charge_accounts_table
CREATE TABLE IF NOT EXISTS rssys.charge_accounts (
    id              BIGSERIAL PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    contact_person  VARCHAR(255) NULL,
    mobileno        VARCHAR(255) NULL,
    created_at      TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at      TIMESTAMP(0) WITHOUT TIME ZONE NULL
);

ALTER TABLE rssys.orlne_pay ADD COLUMN IF NOT EXISTS reference          VARCHAR(100) NULL;
ALTER TABLE rssys.orlne_pay ADD COLUMN IF NOT EXISTS remarks            VARCHAR(255) NULL;
ALTER TABLE rssys.orlne_pay ADD COLUMN IF NOT EXISTS charge_account_id  BIGINT NULL;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'orlne_pay_charge_account_id_foreign') THEN
        ALTER TABLE rssys.orlne_pay
            ADD CONSTRAINT orlne_pay_charge_account_id_foreign FOREIGN KEY (charge_account_id)
                REFERENCES rssys.charge_accounts (id) ON DELETE SET NULL;
    END IF;
END $$;

-- 2026_08_03_000000_add_ord_code_ph_to_orlne_pay_table
ALTER TABLE rssys.orlne_pay ADD COLUMN IF NOT EXISTS ord_code_ph VARCHAR(255) NULL;

UPDATE rssys.orlne_pay
SET ord_code_ph = ordlne_ph.ord_code_ph
FROM rssys.ordlne_ph
WHERE ordlne_ph.id = orlne_pay.ordlne_ph_id
  AND orlne_pay.ord_code_ph IS NULL;

ALTER TABLE rssys.orlne_pay ALTER COLUMN ord_code_ph SET NOT NULL;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'orlne_pay_ord_code_ph_foreign') THEN
        ALTER TABLE rssys.orlne_pay
            ADD CONSTRAINT orlne_pay_ord_code_ph_foreign FOREIGN KEY (ord_code_ph)
                REFERENCES rssys.ordhdr (ord_code_ph) ON DELETE CASCADE;
    END IF;
END $$;

-- 2026_08_03_000001_add_payment_fields_to_ordhdr_table
ALTER TABLE rssys.ordhdr ADD COLUMN IF NOT EXISTS paid_amnt NUMERIC(10, 2) NOT NULL DEFAULT 0.00;
ALTER TABLE rssys.ordhdr ADD COLUMN IF NOT EXISTS is_paid   BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE rssys.ordhdr ADD COLUMN IF NOT EXISTS paid_at   TIMESTAMP(0) WITHOUT TIME ZONE NULL;

-- ----------------------------------------------------------------------------
-- orhdr "slot 2" split-payment columns — legacy table, not a Laravel migration.
-- Types matched exactly to existing orhdr columns (verified via
-- information_schema): pay_code2 varchar(8), payment2/amnt_tendered2 numeric(15,2).
-- Not added to the migrations-tracker block below since orhdr isn't tracked
-- by Laravel at all.
-- ----------------------------------------------------------------------------
ALTER TABLE rssys.orhdr ADD COLUMN IF NOT EXISTS pay_code2      VARCHAR(8) NULL;
ALTER TABLE rssys.orhdr ADD COLUMN IF NOT EXISTS payment2       NUMERIC(15, 2) NULL;
ALTER TABLE rssys.orhdr ADD COLUMN IF NOT EXISTS amnt_tendered2 NUMERIC(15, 2) NULL;

-- ----------------------------------------------------------------------------
-- Laravel migration-tracker bookkeeping.
-- Run this too if the target DB is ever expected to run `php artisan migrate`
-- afterwards — otherwise Laravel will try to re-run these six and fail on
-- the CREATE TABLE / duplicate-column steps. Skip this block if the target
-- is a reporting/replica DB with no migrations table.
-- ----------------------------------------------------------------------------
INSERT INTO rssys.migrations (migration, batch)
SELECT v.migration, (SELECT COALESCE(MAX(batch), 0) + 1 FROM rssys.migrations)
FROM (VALUES
    ('2026_07_15_000000_add_play_hours_fields_to_ordlne_ph_table'),
    ('2026_07_16_000000_add_payment_fields_to_ordlne_ph_table'),
    ('2026_07_31_000000_create_orlne_pay_table'),
    ('2026_07_31_000001_create_charge_accounts_table'),
    ('2026_08_03_000000_add_ord_code_ph_to_orlne_pay_table'),
    ('2026_08_03_000001_add_payment_fields_to_ordhdr_table')
) AS v(migration)
WHERE NOT EXISTS (
    SELECT 1 FROM rssys.migrations m WHERE m.migration = v.migration
);

COMMIT;
