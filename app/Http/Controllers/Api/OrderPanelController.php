<?php

namespace App\Http\Controllers\Api;

use Log,DB;
use Validator;
use Validation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Model\{Geo,AgentProductPrices,Agent,DriverGeo,AgentSlotRoster};

use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Http\Controllers\Api\AgentController;
use App\Traits\{GlobalFunction};

class OrderPanelController extends BaseController
{
    use GlobalFunction;

    public function getProductPrice(Request $request){
      
        
        $validator = Validator::make(request()->all(), [
            'product_variant_sku'  => 'required',
            'schedule_date' => 'required',
        ]);

        $myDate = date('Y-m-d',strtotime( $request->schedule_date));
        $start_time =  $request->start_time;
        $end_time   = $request->end_time;
        $latitude   = $request->latitude ?? '';
        $longitude  = $request->longitude ?? '';

        // $agentController = new AgentController();
        // $geoid           = $agentController->findLocalityByLatLng($latitude, $longitude);
      
        
        // $geoagents_ids    = DriverGeo::where('geo_id', $geoid)->pluck('driver_id');
   
        // \Log::info('getProductPrice');
        // \Log::info($request->all());
   
        $agent = Agent::whereHas('slots',function($q) use($myDate,$start_time,$end_time){
            $q->whereDate('schedule_date', $myDate)
            ->where('start_time', '<=', $start_time)
            ->where('end_time', '>=', $end_time);
            return $q->where('booking_type','!=', 'blocked')->latest();
        })->where(['is_approved'=>1])
                        ->with(['ProductPrices'=>function ($q) use ($request){
                            $q->where('product_variant_sku',$request->product_variant_sku);
                        }])->whereHas('ProductPrices',function ($q) use ($request){
                            $q->where('product_variant_sku',$request->product_variant_sku);
                        } )->withCount('completeOrder')->with(['agentRating','agentlog'])
                        ->get();
            // dd(\DB::getQueryLog());
        $imgproxyurl = 'https://imgproxy.royodispatch.com/insecure/fill/90/90/sm/0/plain/';
        $agents=[];
        $commonSlot=[];
        foreach( $agent as $productPrice){
            $averageTaskComplete   = $this->getDriverTaskDonePercentage( $productPrice->id);
        
            $productPrice->averageTaskComplete = $averageTaskComplete['averageRating'];
          //  unset($productPrice->complete_order);
            if($productPrice->agent){
                $productPrice->agent->image_url =  isset($productPrice->agent->profile_picture) ? $imgproxyurl.Storage::disk('s3')->url($productPrice->agent->profile_picture) : Phumbor::url(URL::to('/asset/images/no-image.png'));
            }

        }
        return response()->json([
            'data' => $agent,
            'status' => 200,
            'message' => __('success'),
        ], 200);
       // pr($agent->toArray());

    }


    public function getProductPriceByAgent(Request $request){
      
        
        $validator = Validator::make(request()->all(), [
            'product_variant_sku'  => 'required',
            'agent_id' => 'required',
        ]);

        $AgentProductPrices = AgentProductPrices::where(['product_variant_sku'=>$request->product_variant_sku,'agent_id'=>$request->agent_id])->first();
        return response()->json([
            'data' => $AgentProductPrices,
            'status' => 200,
            'message' => __('success'),
        ], 200);
       // pr($agent->toArray());

    }
}