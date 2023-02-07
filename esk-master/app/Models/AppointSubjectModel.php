<?php
/**
    * Created By  : Swagatika
	* Created On  : 16-05-2022
	* Module Name : Appoint Subject Master Model
	* Description : Managing appoint subject master database related manupulations.
**/
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointSubjectModel extends Model
{
    protected $table = 'appointSubject';    
    protected $primaryKey = 'appointSubId';
    public $timestamps = false;  
}