<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncentiveConfigModel extends Model
{
    protected $table = 'incentiveConfigMasters';
    protected $primaryKey = 'incentiveConfigId';
    public $timestamps = false;

    public function anexturegender(){
        return $this->hasOne(AnnextureModel::class,'anxtValue','gender')->where('anxtType','=',"GENDER");
    } 

    public function anexturecaste(){
        return $this->hasOne(AnnextureModel::class,'anxtValue','caste')->where('anxtType','=',"CASTE");
    } 

    public function anexturedisability(){
        return $this->hasOne(AnnextureModel::class,'anxtValue','disabilityType')->where('anxtType','=',"DISABILITY_TYPE");
    } 
    
    public function incentivemaster(){
        return $this->hasOne(IncentiveModel::class,'incentiveId','incentiveId')->where('deletedFlag','=',0);
    }  
     
    public function incconfigclasstagged(){
        return $this->hasMany(IncentiveClassTaggedModel::class,'incentiveConfigId','incentiveConfigId');
    } 
}
