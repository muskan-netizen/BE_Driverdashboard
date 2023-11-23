<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DriverRegistrationDocument extends Model
{
    //
    protected $fillable=['file_type','name'];

    public function driver_document(){
        return $this->hasOne('App\Model\AgentDocs','label_name','name');
    }

    public function driver_option(){
        return $this->hasMany('App\Model\DriverRegistrationOption','driver_registration_document_id','id'); 
    }
}
