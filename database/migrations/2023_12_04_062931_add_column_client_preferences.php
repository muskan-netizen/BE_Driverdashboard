<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnClientPreferences extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_preferences', function (Blueprint $table) {
            if(!Schema::hasColumn('client_preferences', 'dashboard_theme')){
                $table->integer('dashboard_theme')->nullable()->default(1);
            }
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
            if(Schema::hasColumn('client_preferences', 'dashboard_theme')){
                $table->dropColumn('dashboard_theme');
            }
        });
    }
}
