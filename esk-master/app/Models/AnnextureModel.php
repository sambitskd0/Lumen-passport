<?php
/**
    * Created By  : Swagatika
	* Created On  : 12-05-2022
	* Module Name : District Block Model
	* Description : Managing block master database related manupulations.
**/
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnextureModel extends Model
{
    protected $table = 'annexture';    
    protected $primaryKey = 'anxtId';    
    public $timestamps = false;  
}


