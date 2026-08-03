<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orlne_pay', function (Blueprint $table) {
            $table->string('ord_code_ph')->nullable()->after('ordlne_ph_id');
        });

        DB::statement('
            UPDATE orlne_pay
            SET ord_code_ph = ordlne_ph.ord_code_ph
            FROM ordlne_ph
            WHERE ordlne_ph.id = orlne_pay.ordlne_ph_id
        ');

        Schema::table('orlne_pay', function (Blueprint $table) {
            $table->string('ord_code_ph')->nullable(false)->change();
            $table->foreign('ord_code_ph')
                ->references('ord_code_ph')
                ->on('ordhdr')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orlne_pay', function (Blueprint $table) {
            $table->dropForeign(['ord_code_ph']);
            $table->dropColumn('ord_code_ph');
        });
    }
};
