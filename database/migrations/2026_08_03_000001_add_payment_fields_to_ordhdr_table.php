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
        Schema::table('ordhdr', function (Blueprint $table) {
            if (!Schema::hasColumn('ordhdr', 'paid_amnt')) {
                $table->decimal('paid_amnt', 10, 2)->default(0.00)->after('disc_amnt');
            }
            if (!Schema::hasColumn('ordhdr', 'is_paid')) {
                $table->boolean('is_paid')->default(false)->after('paid_amnt');
            }
            if (!Schema::hasColumn('ordhdr', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('is_paid');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordhdr', function (Blueprint $table) {
            $table->dropColumn(['paid_amnt', 'is_paid', 'paid_at']);
        });
    }
};
