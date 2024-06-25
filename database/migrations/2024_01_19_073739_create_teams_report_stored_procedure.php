<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeamsReportStoredProcedure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $query = '
        CREATE PROCEDURE teams_report(
            IN startdate DATETIME,
            IN enddate DATETIME
        )
        BEGIN
            SELECT
                teams.*,
                COUNT(DISTINCT ag.id) AS total_agents,
                SUM(ag.is_available = 1) AS online_agents,
                SUM(ag.is_available = 0) AS offline_agents,
                agents.id AS agent_id,
                agents.name AS agent_name,
                agents.profile_picture AS profile_picture,
                agents.is_available AS is_available,
                COUNT(DISTINCT orders.id) AS order_count,
                COUNT(DISTINCT tasks.id) AS agent_task_count,
                orders.id AS order_id,
                tasks.id AS task_id,
                tasks.task_order,
                locations.id AS location_id,
                locations.latitude,
                locations.longitude
            FROM teams
            LEFT JOIN agents AS ag ON ag.team_id = teams.id AND ag.deleted_at IS NULL
            LEFT JOIN agents ON agents.team_id = teams.id AND agents.deleted_at IS NULL
            LEFT JOIN orders ON orders.driver_id = agents.id AND orders.order_time >= startdate AND orders.order_time <= enddate
            LEFT JOIN tasks ON tasks.order_id = orders.id
            LEFT JOIN locations ON tasks.location_id = locations.id;
        END;
    ';

        \DB::unprepared($query);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $query = 'DROP PROCEDURE IF EXISTS teams_report;';
        \DB::unprepared($query);
    }
}
