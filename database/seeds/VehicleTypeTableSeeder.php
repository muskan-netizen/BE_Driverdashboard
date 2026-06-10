<?php
use Illuminate\Database\Seeder;
use App\Model\VehicleType;

class VehicleTypeTableSeeder extends Seeder
{
    
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $option_count = DB::table('vehicle_types')->count();
        
        $vehicle__type = array(
            array(
                'id' => 1,
                'name' => 'onfoot'
            ),
            array(
                'id' => 2,
                'name' => 'bycycle'
            ),
            array(
                'id' => 3,
                'name' => 'motorbike'
            ),
            array(
                'id' => 4,
                'name' => 'car'
            ),
            array(
                'id' => 5,
                'name' => 'truck'
            ),
            array(
                'id' => 6,
                'name' => 'auto'
            )
        );
        
        if ($option_count == 0) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('vehicle_types')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            DB::table('vehicle_types')->insert($vehicle__type);
        } else {
            foreach ($vehicle__type as $type) {
                $vehicleType = VehicleType::where('id', $type['id'])->first();
                
                if ($vehicleType !== null) {
                    $vehicleType->update([
                        'name' => $type['name']
                    ]);
                } else {
                    $vehicleType = VehicleType::create([
                        'id' => $type['id'],
                        'name' => $type['name']
                    ]);
                }
            }
        }
    }
}