<?php
/**
    * Created By  : Saubhagya
	* Created On  : 12-05-2022
	* Module Name : Cluster Master Model
	* Description : Managing Cluster master database related manupulations.
**/
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClusterModel extends Model
{
    protected $table = 'clusters';     
    protected $primaryKey = 'clusterId';
    public $timestamps = false; 

    public function district(){
        return $this->hasOne(DistrictModel::class,'districtId','districtId');
    } 
    
    public function block(){
        return $this->hasOne(BlockModel::class,'blockId','blockId');
    } 
} 