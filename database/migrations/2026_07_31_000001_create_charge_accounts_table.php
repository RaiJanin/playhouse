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
        Schema::create('charge_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('mobileno')->nullable();
            $table->timestamps();
        });

        Schema::table('orlne_pay', function (Blueprint $table) {
            $table->string('reference', 100)->nullable()->after('change_amnt');
            $table->string('remarks', 255)->nullable()->after('reference');
            $table->foreignId('charge_account_id')->nullable()->after('remarks')
                ->constrained('charge_accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orlne_pay', function (Blueprint $table) {
            $table->dropConstrainedForeignId('charge_account_id');
            $table->dropColumn(['reference', 'remarks']);
        });

        Schema::dropIfExists('charge_accounts');
    }
};
