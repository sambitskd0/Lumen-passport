<?php

/**
 * Created By  : Sambit Kumar Dalai
 * Created On  : 21-06-2022
 * Module Name : EmployeeProfile Model
 * Description : Managing EmployeeProfile.
 **/

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeProfileModel extends Model
{
    protected $table = 'm_employee_profile_master';
    protected $primaryKey = 'intProfileId';
    public $timestamps = false;
}
