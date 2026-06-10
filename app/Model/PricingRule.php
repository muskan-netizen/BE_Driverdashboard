<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    protected $table = 'price_rules';
    protected $fillable = ['name','start_date_time','end_date_time','is_default','geo_id','team_id','team_tag_id','driver_tag_id','base_price','base_duration','base_distance','base_waiting','duration_price','waiting_price','distance_fee','cancel_fee','agent_commission_percentage','agent_commission_fixed','freelancer_commission_percentage','agent_commission_fixed', 'apply_timetable', 'base_price_minimum', 'base_duration_minimum', 'base_distance_minimum', 'base_waiting_minimum', 'duration_price_minimum', 'waiting_price_minimum', 'distance_fee_minimum', 'base_price_maximum', 'base_duration_maximum', 'base_distance_maximum', 'base_waiting_maximum', 'duration_price_maximum', 'waiting_price_maximum', 'distance_fee_maximum'];


    /* public function team(){
        return $this->belongsTo('App\Model\Team');
    }

    public function tagsForAgent(){
        return $this->belongsTo('App\Model\TagsForAgent','driver_tag_id','id');
    } */

    public function priceRuleTimeframe(){
        return $this->hasMany('App\Model\priceRuleTimeframe', 'pricing_rule_id', 'id');
        
    }

    public function priceRuleTags(){
        return $this->hasMany('App\Model\priceRuleTag','pricing_rule_id','id');
    }

    public function distanceRules(){
        return $this->hasMany('App\Model\DistanceWisePricingRule','price_rule_id','id');
    }

}
