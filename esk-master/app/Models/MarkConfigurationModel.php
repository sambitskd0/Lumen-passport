<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarkConfigurationModel extends Model
{
    //
    public $table ='markConfiguration';
    public $timestamps = false;
    public $primaryKey='markConfigurationId';
    public static function markconfigurationValidation($arrPageData,$id=''){
        $ValidateRule = [
                'examType' => 'bail|required|numeric',
                'classId' => 'bail|required|numeric',
                // |unique:markConfiguration,classId,'.',markConfigurationId,streamId,'.trim($arrPageData['streamId']).',groupId,'.trim($arrPageData['groupId']).',examinationTypeId,'.trim($arrPageData['examType']).',deletedFlag,0
                'streamId' => 'bail|required_if:classId,==,11,12|numeric',
                'groupId' => 'bail|required_if:streamId,==,3|numeric',
               
        ];
        $ValidateMsg = [
               
                'examType.required' => 'ExamType is mandatory.',
                'classId.required' => 'Class is mandatory.',
                // 'classId.unique' => 'For this combination of class ,stream and group mark configuration has already been added.',
                'streamId.required_if' => 'Stream is mandatory.',
                'groupId.required_if' => 'Group is mandatory.',
               
        ];
        $validator = \Validator::make($arrPageData,
        $ValidateRule,
        $ValidateMsg);
        // print_r($validator->errors());exit;
        return $validator;
        }
}
