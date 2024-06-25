<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentOrderLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_order_logs', function (Blueprint $table) 
            {
            $table->id();
			$table->bigInteger('agent_id')->unsigned();
			$table->bigInteger('current_task_id')->unsigned();
			$table->decimal('lat', 10, 8)->default(0);
			$table->decimal('long', 12, 8)->default(0);
			$table->smallInteger('battery_level')->default(0);
			$table->string('device_type', 20)->nullable();
			$table->string('app_version', 10)->nullable();
			$table->string('current_speed', 20)->nullable();
			$table->tinyInteger('on_route')->default(1)->comment('for task -> 1 for Yes,n for 0');
            $table->decimal('heading_angle', 12, 8)->nullable();
			$table->timestamps();
		});

		Schema::table('agent_logs', function (Blueprint $table) {
			
            $table->index('created_at');
		});
    
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agent_order_logs');
    }
}
