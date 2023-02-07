<?php
/**
    * Created By  : Manoj Kumar Baliarsingh
	* Created On  : 12-05-2022
	* Module Name : Village Master Model
	* Description : Managing Village master database related manupulations.
**/
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class VillageModel extends Model
{
    protected $table = 'villages';    
    protected $primaryKey = 'villageId';
    public $timestamps = false;

    public function district(){
        return $this->hasOne(DistrictModel::class,'districtId','districtId');
    }

    public function block(){
        return $this->hasOne(BlockModel::class,'blockId','blockId');
    }
    
    public function nagarnigam(){
        return $this->hasOne(NagarNigamModel::class,'nagarId','panchayatId');
    }

}