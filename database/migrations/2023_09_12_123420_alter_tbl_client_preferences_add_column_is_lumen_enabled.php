<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTblClientPreferencesAddColumnIsLumenEnabled extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_preferences', function (Blueprint $table) {
            $table->tinyInteger('is_lumen_enabled')->default(0);
            $table->string('lumen_domain_url','255')->default(null);
            $table->string('lumen_access_token','60')->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_preferences', function (Blueprint $table) {
            $table->dropColumn('is_lumen_enabled');
            $table->dropColumn('lumen_domain_url');
            $table->dropColumn('lumen_access_token');
        });
    }
}
