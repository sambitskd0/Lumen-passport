<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectTaggingModel extends Model
{
    //
    protected $table = 'subjectTagging';     
     protected $primaryKey = 'subTagId';
    public $timestamps = false; 

    
    public function annexture(){
        return $this->hasOne(AnnextureModel::class,'anxtValue','classId')->where('anxtType','CLASS_TYPE ');
    } 
    
    public function subject(){
        return $this->hasOne(subjectModel::class,'subjectId','subjectId');
    }  
    
}
