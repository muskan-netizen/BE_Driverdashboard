<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateStoredProcedureGetLatestLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
            CREATE PROCEDURE GetLatestAgentLogs(IN p_agent_id INT, IN p_is_available BOOLEAN)
            BEGIN
                SET p_agent_id = NULLIF(p_agent_id, -1);
                SET p_is_available = NULLIF(p_is_available, -1);
            SELECT
                al.id AS log_id,
                al.lat,
                al.device_type,
                al.battery_level,
                al.`long`,
                al.created_at,
                a.id AS agent_id,
                a.name AS agent_name,
                a.phone_number AS phone_number,
                a.profile_picture AS image_url,
                a.is_available
                 FROM agents a
            LEFT JOIN (
                SELECT
                id,
                agent_id,
                lat,
                `long`,
                device_type,
                battery_level,
                created_at,
                ROW_NUMBER() OVER (PARTITION BY agent_id ORDER BY created_at DESC) AS rn
                FROM agent_logs
            ) AS al ON a.id = al.agent_id AND al.rn = 1
            WHERE (p_agent_id IS NULL OR a.id = p_agent_id)
            AND (p_is_available IS NULL OR a.is_available = p_is_available)
            AND al.id IS NOT NULL;
            END
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS GetLatestAgentLogs');
    }
}
