<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sms_blast_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_blast_id')->constrained('sms_blasts')->onDelete('cascade');
            $table->string('recipient_type'); // parent, guardian
            $table->string('recipient_id'); // M06 d_code
            $table->string('recipient_name');
            $table->string('mobile_number');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['sms_blast_id', 'status']);
            $table->index('mobile_number');
        });
    }

    protected $sqlCreateTables = "
        CREATE TABLE rssys.sms_blast_recipients (
            id BIGSERIAL PRIMARY KEY,
            sms_blast_id BIGINT NOT NULL,
            recipient_type VARCHAR(255) NOT NULL, -- parent, guardian
            recipient_id VARCHAR(255) NOT NULL,   -- M06 d_code
            recipient_name VARCHAR(255) NOT NULL,
            mobile_number VARCHAR(255) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending'
                CHECK (status IN ('pending', 'sent', 'failed')),
            error_message TEXT NULL,
            sent_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            CONSTRAINT sms_blast_recipients_sms_blast_id_fk
                FOREIGN KEY (sms_blast_id)
                REFERENCES rssys.sms_blasts(id)
                ON DELETE CASCADE
        );

        CREATE INDEX sms_blast_recipients_sms_blast_id_status_idx
            ON rssys.sms_blast_recipients (sms_blast_id, status);

        CREATE INDEX sms_blast_recipients_mobile_number_idx
            ON rssys.sms_blast_recipients (mobile_number);
    ";
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_blast_recipients');
    }
};
