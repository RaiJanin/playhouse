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
        Schema::table('ordlne_ph', function (Blueprint $table) {
            if (!Schema::hasColumn('ordlne_ph', 'cash_tendered')) {
                $table->decimal('cash_tendered', 10, 2)->nullable()->after('others_amnt');
            }
            if (!Schema::hasColumn('ordlne_ph', 'change_amnt')) {
                $table->decimal('change_amnt', 10, 2)->nullable()->after('cash_tendered');
            }
            if (!Schema::hasColumn('ordlne_ph', 'is_paid')) {
                $table->boolean('is_paid')->default(false)->after('change_amnt');
            }
            if (!Schema::hasColumn('ordlne_ph', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('is_paid');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordlne_ph', function (Blueprint $table) {
            $table->dropColumn(['cash_tendered', 'change_amnt', 'is_paid', 'paid_at']);
        });
    }
};
