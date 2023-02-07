<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceInfoModel extends Model
{
    //
    public $table ='deviceInformation';
    public $timestamps = false;
    public $primaryKey='deviceInfoId';

    public function district(){
        return $this->hasOne(DistrictModel::class,'districtId','districtId');
    }

    public function block(){
        return $this->hasOne(BlockModel::class,'blockId','blockId');
    }
    public function cluster(){
        return $this->hasOne(clusterModel::class,'clusterId','clusterId');
    }

}
