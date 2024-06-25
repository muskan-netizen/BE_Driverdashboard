<?php
namespace App\Traits;
use DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use App\Model\{Agent, AgentSlot,AgentSlotRoster};
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

trait DashboardSocketTrait{

    //------------------------------Function created by Rajat Kumar--------------------------//
    public static function FireEvent($data)
    {
        
       // try {
            event(new \App\Events\SendMessage($data));
        // }catch (\Exception $e) {
        //     \Log::info($e->getMessage());
        //     return 2;
        // }
    }
   

}
