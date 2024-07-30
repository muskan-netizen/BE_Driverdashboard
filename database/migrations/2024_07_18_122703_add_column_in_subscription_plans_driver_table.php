<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnInSubscriptionPlansDriverTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_plans_driver', function (Blueprint $table) {
            $table->integer('no_of_rides')->after('frequency')->nullable();
            $table->string('type_of_sub')->after('no_of_rides')->nullable();        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_plans_driver', function (Blueprint $table) {
            $table->dropColumn('no_of_rides');
            $table->dropColumn('type_of_sub');        });
    }
}