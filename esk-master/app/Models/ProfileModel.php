<?php
/**
    * Created By  : Ayasakanta Swain
	* Created On  : 02-Jun-2022
	* Module Name : Profile Model
	* Description : Managing Profile.
**/
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileModel extends Model
{
    protected $table = 'm_employee_profile_master';    
    protected $primaryKey = 'intProfileId';
    public $timestamps = false;  
}