<?php

/**
 * Created By  : Sambit Kumar Dalai
 * Created On  : 20-05-2022
 * Module Name : Authentication Model
 * Description : Managing Authentication.
 **/

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpModel extends Model
{
    protected $table = 'userOtp';
    protected $primaryKey = 'otpId';
    public $timestamps = false;
}
