<?php
namespace App\Traits;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use GuzzleHttp\Client;
use App\Model\ClientPreference;
use Log;
use Unifonic;
trait TollFee{
 
    /**
     * toll_fee
     *
     * @return void
     */

    // function to get toll fee from given origin and destinations
    public function toll_fee($latitude = array(), $longitude = array(), $toll_passes = 'IN_FASTAG', $VehicleEmissionType = 'GASOLINE', $travelMode = 'TAXI')
    {
        $ClientPreference = ClientPreference::where('id', 1)->first();
 
        //Sample Api location input
        $origin = [
            'latitude'=> '19.076090',
            'longitude' => '72.877426'
        ];
        $destination = [
            'latitude'=> '28.613939',
            'longitude' => '77.209023'
        ];
        //intermediate destinations
        $waypoints = [
            
        ];

        $j = 0;
        for($i = 0;$i < count($latitude); $i++)
        {
            if($i == 0)
            {
                $origin['latitude'] = $latitude[$i];
                $origin['longitude'] = $longitude[$i];
            }
            if($i > 0 && (count($latitude)-1) > $i){
                $waypoints[$j]['location']['latLng']['latitude'] = $latitude[$i];
                $waypoints[$j]['location']['latLng']['longitude'] = $longitude[$i];
                $j++;
            }
            if((count($latitude)-1) == $i)
            {
                $destination['latitude'] = $latitude[$i];
                $destination['longitude'] = $longitude[$i];
            }
        }

        $headers = array('X-Goog-Api-Key: '.(!empty($ClientPreference)?$ClientPreference->map_key_1:''),
                'Content-Type: application/json',
                'X-Goog-FieldMask: routes.duration,routes.distanceMeters,routes.travelAdvisory.tollInfo,routes.legs.travelAdvisory.tollInfo,routes.legs.duration,routes.legs.distanceMeters',
                );
                
        $url = "https://routes.googleapis.com/directions/v2:computeRoutes";

        $data = [
                'origin' => [
                    'location' => [
                        'latLng' => $origin
                    ]
                ],
                'destination' => [
                    'location' => [
                        'latLng' => $destination
                    ]
                ],
                'intermediates' => $waypoints,
                "extraComputations"=>["TOLLS"],
                "travelMode" => $travelMode,
                "units" =>  "metric",
                "route_modifiers" => [
                    "vehicle_info" => [
                        "emission_type" => $VehicleEmissionType
                    ],
                    "toll_passes" => $toll_passes
                ]
            ];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            // Receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $apiResponse = curl_exec($ch);
            
            curl_close($ch);
            $apiResponse = json_decode($apiResponse);

            $toll_array = array();
            $toll_array['duration'] = 0;
            $toll_array['distance'] = 0;
            $toll_array['currency'] = '';
            $toll_array['toll_amount'] = 0;
            if(!empty($apiResponse->routes))
            {
                foreach($apiResponse->routes as $routes){
                    if ($ClientPreference->distance_unit == 'metric') {
                        $toll_array['distance'] = round((isset($routes->distanceMeters)?$routes->distanceMeters:0.00)/1000, 2);      //km
                    } else {
                        $toll_array['distance'] = round((isset($routes->distanceMeters)?$routes->distanceMeters:0.00)/1609.34, 2);  //mile
                    }
                    $durationInSec              = isset($routes->duration)?str_replace('s', '', $routes->duration):0;
                    $toll_array['duration']     = round($durationInSec/60);
                    if(isset($routes->travelAdvisory) && !empty($routes->travelAdvisory)){
                        if(isset($routes->travelAdvisory->tollInfo) && !empty($routes->travelAdvisory->tollInfo)){
                            foreach($routes->travelAdvisory->tollInfo->estimatedPrice as $estimatedPrice){
                                $toll_array['currency'] = $estimatedPrice->currencyCode;
                                $toll_array['toll_amount'] = $estimatedPrice->units;
                            }
                        }
                    }
                }
            }else{
                $toll_array['distance'] = 0;
                $toll_array['duration'] = 0;
                $toll_array['currency'] = '';
                $toll_array['toll_amount'] = 0;
            }
            return $toll_array;
    }


   

}
