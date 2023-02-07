<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackCategoryModel extends Model
{
   protected $table = 'feedbackCategories';
   protected $primaryKey = 'catId';
   public $timestamps = false;
}
