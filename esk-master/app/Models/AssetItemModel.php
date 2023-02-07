<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetItemModel extends Model
{
    protected $table = 'assetItems';
   protected $primaryKey = 'assetItemId';
   public $timestamps = false;
  
public function anexture(){
    return $this->hasOne(AnnextureModel::class,'anxtValue','assetType')->where('anxtType','=','ASSET_TYPE');
} 

public function incentiveunit(){
    return $this->hasOne(AnnextureModel::class,'anxtValue','itemUnit')->where('anxtType','=','Incentive_Unit');
} 

public function assetcategories()
{
    return $this->hasOne(AssetCategoryModel::class, 'assetCatId', 'assetCatId');
}

}   
