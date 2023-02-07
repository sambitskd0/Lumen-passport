<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MDMItemModel extends Model
{
    protected $table = 'mdmItems';
   protected $primaryKey = 'itemId';
   public $timestamps = false;
}
