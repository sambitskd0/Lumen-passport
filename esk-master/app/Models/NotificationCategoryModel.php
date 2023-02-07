<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationCategoryModel extends Model
{
    protected $table = 'notification';
   protected $primaryKey = 'categoryId';
   public $timestamps = false;
}
