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
            if (!Schema::hasColumn('ordlne_ph', 'others_amnt')) {
                $table->decimal('others_amnt', 10, 2)->default(0)->after('lne_xtra_chrg');
            }
            if (!Schema::hasColumn('ordlne_ph', 'disc_amnt')) {
                $table->decimal('disc_amnt', 10, 2)->default(0)->after('disc_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordlne_ph', function (Blueprint $table) {
            $table->dropColumn(['others_amnt', 'disc_amnt']);
        });
    }
};
