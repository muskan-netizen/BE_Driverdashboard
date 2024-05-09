<?php
namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use App\Model\ {
    ClientPreference,
    ClientPreferenceAdditional,
    Client
};
use GuzzleHttp\Client as GCLIENT;
use Log;
use Storage;

trait ClientPreferenceManager
{

    public $client_preference_fillable_key = [
        'pickup_type',
        'drop_type',
        'hold_to_start',
        'hold_to_arrive',
        'hold_to_pick',
        'hold_to_complete',
        'is_attendence',
        'idle_time',
        'reffered_to_amount',
        'reffered_by_amount',
        'refferel_to_agent_amount',
        'refferel_by_agent_amount',
    ];

    /**
     * updatePreferenceAdditional
     *
     * @param mixed $$request
     * @return void harbans :)
     *        
     */
    public function updatePreferenceAdditional($request = [])
    {
        $validated_keys = $request->only($this->client_preference_fillable_key);
        $client = Client::first();
        foreach ($validated_keys as $key => $value) {
            if ($key == 'is_attendence' && $value == 'on') {
                $value = 1;
            }

            ClientPreferenceAdditional::updateOrCreate([
                'key_name' => $key,
                'client_code' => $client->code
            ], [
                'key_name' => $key,
                'key_value' => $value,
                'client_code' => $client->code,
                'client_id' => $client->id
            ]);
        }
        return 1;
    }
}
