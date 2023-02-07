<?php

/**
 * Created By  : Sambit Kumar Dalai
 * Created On  : 24-06-2022
 * Module Name : Designation Model
 **/

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignationModel extends Model
{
    protected $table = 'm_designation';
    protected $primaryKey = 'intDesignationId';
    public $timestamps = false;
}
