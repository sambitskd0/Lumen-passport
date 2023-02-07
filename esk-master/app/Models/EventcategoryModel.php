<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventcategoryModel extends Model
{
    //
    public $table ='eventCategory';
    public $timestamps = false;
    public $primaryKey='eventCategoryId';
     public function eventtype(){
        return $this->hasOne(EventTypeModel::class,'eventId','eventCategoryId');
     } 
}
