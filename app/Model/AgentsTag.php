<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AgentsTag extends Model
{
    //

    public function agent()
    {
        return $this->belongsTo('App\Model\Agent');
    }
    public function tags()
    {

        return $this->hasOne('App\Model\TagsForAgent', 'id', 'tag_id');
    }
}
