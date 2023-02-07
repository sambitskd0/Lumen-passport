<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetCategoryModel extends Model
{
   protected $table = 'assetCategories';
   protected $primaryKey = 'assetCatId';
   public $timestamps = false;
  
   public function anexture(){
      return $this->hasOne(AnnextureModel::class,'anxtValue','assetType')->where('anxtType','=','ASSET_TYPE')->where('deletedFlag',0);
  } 
   
}
