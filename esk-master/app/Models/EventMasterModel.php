<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventMasterModel extends Model
{
    //
    public $table ='eventMaster';
    public $timestamps = false;
    public $primaryKey='eventMasterId';
    public function eventtype(){
        return $this->hasOne(EventTypeModel::class,'eventId','eventId');
     } 
     public function eventcategory(){
        return $this->hasOne(EventcategoryModel::class,'eventCategoryId','categoryId');
     } 
}
