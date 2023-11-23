<?php

namespace App\Model;

use App\OrderWaitTimeLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
class Order extends Model
{
    protected $fillable = ['customer_id','scheduled_date_time','recipient_phone','Recipient_email','task_description','images_array','auto_alloction','driver_id','key_value_set','order_time','order_type','note','status'
    ,'cash_to_be_collected','base_price','base_duration','base_distance','base_waiting','duration_price','waiting_price','distance_fee','cancel_fee','agent_commission_percentage','agent_commission_fixed','freelancer_commission_percentage',
     'freelancer_commission_fixed','actual_time','actual_distance','order_cost','driver_cost','proof_image','proof_signature','unique_id','net_quantity','call_back_url', 'completion_otp','order_number','type','friend_name','friend_phone_number',
     'request_type','is_restricted', 'vendor_id', 'order_vendor_id', 'sync_order_id','dbname', 'vendor_name','toll_fee', 'available_seats', 'no_seats_for_pooling', 'is_cab_pooling', 'is_one_push_booking','rejectable_order','refer_driver_id','is_comm_settled','order_pre_time','buffer_time','waiting_time','notify_all'];

    protected $appends = ['total_waiting_amount','total_waiting_time'];

    public function customer(){
        return $this->hasOne('App\Model\Customer', 'id', 'customer_id');
        
    }
    public function location(){
        return $this->hasMany('App\Model\Location', 'customer_id', 'customer_id')
                    ->select('latitude', 'longitude', 'short_name', 'address', 'post_code', 'customer_id', 'flat_no');
        
    }

    public function task(){ 
        return $this->hasMany('App\Model\Task', 'order_id', 'id')->orderBy('task_order')->orderBy('id');
    }

    public function pickup_task_first(){
        return $this->hasOne('App\Model\Task')->where('task_type_id', 1);
    }

    public function pickup_task(){
        return $this->hasMany('App\Model\Task', 'order_id', 'id')->where('task_type_id', 1);
    }

    public function dropoff_task(){
        return $this->hasMany('App\Model\Task', 'order_id', 'id')->where('task_type_id', 2)->orderBy('task_order', 'desc');
    }

    public function agent(){
        return $this->belongsTo('App\Model\Agent', 'driver_id', 'id')->select('id', 'team_id', 'name', 'type', 'phone_number','make_model', 'plate_number', 'profile_picture', 'vehicle_type_id','color', 'is_pooling_available', 'is_available','device_token');
        
    }

    public function teamtags(){
        return $this->belongsToMany('App\Model\TaskTeamTag', 'task_team_tags','task_id','tag_id');
    }

    public function drivertags(){
        return $this->belongsToMany('App\Model\TaskDriverTag', 'task_driver_tags','task_id','tag_id');
    }

    public function drivertag_combination(){
        return $this->hasMany('App\Model\TaskDriverTag', 'task_id', 'id');
    }

    public function customerdata()
    {
      return $this->hasOne('App\Model\Customer');
    }

    public function taskFirst()
    {
      return $this->task()->where('pricing_rule_id', 1);
    }

    public function allteamtags(){
        return $this->hasMany('App\Model\TaskTeamTag','task_id','id');
    }

    public function task_rejects(){
        return $this->hasMany('App\Model\TaskReject','order_id','id');
    }

    public function first_task_order_by_date(){
        return $this->hasOne('');
    }

    public function getTaskImagesAttribute($value)
    {
      $array = array();
      $imgarray = array();
      
      if (isset($value) && !empty($value)) {
        $array = explode(",", $value);
        } else {
            $array = []; 
        }

        $can = Storage::disk('s3')->url('image.png');
        $lastbaseurl = str_replace('image.png', '', $can);

        if(count($array) > 0){
            foreach ($array as $item)  {
                $imgarray[] = $lastbaseurl.$item;
           }
        }
        
        return $imgarray;
    }
    
    public function fleet(){
        return $this->belongsTo('App\Model\Fleet')->select('id','name','registration_name','color','make','model','year');
    }
    
    public function getAgentPayout() {
        return $this->hasOne('App\Model\AgentPayout', 'order_id', 'id');
    }
    public function additionData() {
        return $this->hasMany('App\Model\OrderAdditionData', 'order_id', 'id');
      
    }
    public function userRating(){
        return $this->hasOne('App\Model\UserRating', 'order_id', 'id');
        
    }

    public function waitingTimeLogs()
    {
        return $this->hasMany(OrderWaitTimeLog::class);
    }

    public function getTotalWaitingAmountAttribute()
    {
        return $this->waitingTimeLogs()->sum('amount') + floor($this->totalSeconds()/60) * $this->duration_price ?? 0; 
    }

    public function totalSeconds()
    {
       return $this->waitingTimeLogs()->get()->reduce(function($carry,$log){
            $seconds = explode(':',$log->wait_time)[1];
            return $carry + $seconds;
        });
    }

    public function getTotalWaitingTimeAttribute()
    {
        $minutes = $this->waitingTimeLogs()->get()->reduce(function($carry,$log){
            $seconds = explode(':',$log->wait_time)[0];
            return $carry + $seconds;
        });

        if($this->totalSeconds() >= 60){
            $increaseMin = 1;
            $remainingSec = $this->totalSeconds() - 60;
            if($remainingSec < 10){
                $remainingSec = (int)'0'.$remainingSec; 
            }
        }else{
            $increaseMin = 0;
            $remainingSec = $this->totalSeconds() ;
        }
        return (float) ($minutes + $increaseMin) .'.'.$remainingSec;
    }
}
