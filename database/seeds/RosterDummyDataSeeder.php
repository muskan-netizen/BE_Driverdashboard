<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class RosterDummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       
        // Get current datetime
        $date = now()->toDateTimeString();

        try {
            // Define the schema name
            $schemaName = 'royodelivery_db';

            // Set the connection configuration
            $default = [
                'driver' => env('DB_CONNECTION', 'mysql'),
                'host' => env('DB_HOST'),
                'port' => env('DB_PORT'),
                'database' => $schemaName,
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => false,
                'engine' => null
            ];

            // Set the connection configuration for the specified schema
            Config::set("database.connections.$schemaName", $default);

            // Set the default database connection to the specified schema
            config(["database.connections.mysql.database" => $schemaName]);

            // Start the transaction
            DB::beginTransaction();

            // Get Faker instance
            $faker = \Faker\Factory::create();

            // Loop to insert 10,000 dummy entries
            for ($i = 0; $i < 10000; $i++) {
                DB::connection($schemaName)->table('rosters')->insert([
                    'order_id' => '500',
                    'driver_id' => 5,
                    'notification_time' => now(),
                    'type' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'client_code' => $faker->randomNumber(),
                    'device_type' => $faker->randomElement(['iOS', 'Android']),
                    'device_token' => $faker->uuid,
                    'detail_id' => $faker->unique()->randomNumber(),
                    'status' => 0,
                    'request_type' => $faker->randomElement(['request_type1', 'request_type2']),
                    'cash_to_be_collected' => $faker->randomFloat(2, 0, 1000),
                    'notification_befor_time' => $faker->dateTimeBetween('-1 day', 'now'),
                    'batch_no' => 'batch_' . str_pad(floor($faker->unique()->numberBetween(0, 10000) / 1000), 2, '0', STR_PAD_LEFT),
                    'is_particular_driver' => $faker->numberBetween(0, 1),
                ]);
            }

            // Commit the transaction
            DB::commit();
        } catch (\Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            throw $e;
        }
    }
}
