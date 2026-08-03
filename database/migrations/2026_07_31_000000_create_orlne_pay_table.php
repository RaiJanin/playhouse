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
        Schema::create('orlne_pay', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordlne_ph_id')->constrained('ordlne_ph')->cascadeOnDelete();
            $table->string('payment_method', 20);
            $table->decimal('amount', 10, 2);
            $table->decimal('cash_tendered', 10, 2)->nullable();
            $table->decimal('change_amnt', 10, 2)->nullable();
            $table->timestamp('paid_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orlne_pay');
    }
};
