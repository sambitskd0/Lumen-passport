<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncentiveClassTaggedModel extends Model
{
    protected $table = 'incConfigClassTagged';    
    protected $primaryKey = 'incConfigClassTaggedId';
    public $timestamps = false; 
   
    public function incentivemaster(){
        return $this->hasOne(IncentiveConfigModel::class,'incentiveConfigId','incentiveConfigId')->where('deletedFlag','=',0);
    }  
}
