<?php
/**
    * Created By  : Saubhagya
	* Created On  : 12-05-2022
	* Module Name : Nagarnigam Master Model
	* Description : Managing Nagarnigam master database related manupulations.
**/
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class NagarNigamModel extends Model
{
    protected $table = 'nagarnigams';     
    protected $primaryKey = 'nagarId';
    public $timestamps = false;  
    
    public function district(){
        return $this->hasOne(DistrictModel::class,'districtId','districtId');
    } 
    public function block(){
        return $this->hasOne(BlockModel::class,'blockId','blockId')->where('blockId','<>','0');
    } 
}