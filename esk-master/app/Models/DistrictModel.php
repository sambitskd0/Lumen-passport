<?php
/**
    * Created By  : Swagatika
	* Created On  : 11-05-2022
	* Module Name : District Master Model
	* Description : Managing district master database related manupulations.
**/
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistrictModel extends Model
{
    protected $table = 'districts';    
    protected $primaryKey = 'districtId';
    public $timestamps = false;  
}