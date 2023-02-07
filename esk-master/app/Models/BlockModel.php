<?php
/**
    * Created By  : Swagatika
	* Created On  : 12-05-2022
	* Module Name : District Block Model
	* Description : Managing block master database related manupulations.
**/
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockModel extends Model
{
    protected $table = 'blocks';    
    protected $primaryKey = 'blockId';    
    public $timestamps = false;  

    public function district(){
        return $this->hasOne(DistrictModel::class,'districtId','districtId');
    }
}

