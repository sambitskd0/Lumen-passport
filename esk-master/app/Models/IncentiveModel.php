<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncentiveModel extends Model
{
    protected $table = 'incentiveMasters';
    protected $primaryKey = 'incentiveId';
    public $timestamps = false;

    public function anexture(){
        return $this->hasOne(AnnextureModel::class,'anxtValue','incentiveUnit')->where('anxtType','=','Incentive_Unit');
    } 
}



  
