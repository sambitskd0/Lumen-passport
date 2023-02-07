<?php

/**
 * Created By  : Nitish Nanda
 * Created On  : 22-06-2022
 * Module Name : Master Module
 * Description : Manage Examinationmaster Add,View,Edit,Delete
 **/

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\AnnextureModel;
use App\Models\ExaminationMasterModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;


class ExaminationMasterController extends Controller
{
    
 /* Created By  :  Nitish Nanda ||  Created On  : 22-06-2022 || Component Name : ExaminationMaster|| Description:get className From Annex */   
public function getClassName($id = NULL) 
    {
        $msg = '';
        try{
        $statusCode = config('constant.SUCCESS_CODE');
        $queryData = AnnextureModel::select('anxtValue','anxtName','createdOn')
        ->where([['deletedFlag', 0]])->where('anxtType','CLASS_TYPE');
        if (!empty($id)) {
            $id = Crypt::decryptString($id);
            $queryData->where([['anxtValue', $id]]);
        }
        $responseData = $queryData->get();
      if ($responseData->isEmpty()) {
            $msg = 'No record found.';
        } else {
         
            $response = array();
            foreach ($responseData as $res) {
                $res['encId']               = Crypt::encryptString($res->anxtValue);
                 $res['anxtName']           = $res->CLASS_TYPE->anxtName;
                 $res['createdOn']          = $res->createdOn;
                array_push($response, $res);
            }
            $responseData = (count($response) > 1) ? $response : $response[0];
        }
    }
    catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'ExaminationMasterController',
            'Method'     => 'getClassName',
            'Error'      => $t->getMessage()
        ]);
        $status = "ERROR";
        $statusCode = config('constant.EXCEPTION_CODE');
        $msg = config('constant.EXCEPTION_MESSAGE');              
    }
        return response()->json([
            "status" => 'SUCCESS',
            "statusCode" => $statusCode,
            "msg" => $msg,
            "data" => $responseData
        ], $statusCode);
    }
   /* Created By  :  Nitish Nanda ||  Created On  : 22-06-2022 || Component Name :EventMaster || Description:Add ExaminationMaster */
 public function addExaminationMaster(Request $request){
        
    $status = "ERROR";
    DB::beginTransaction();
    try{
    $arrData = $request->all();
     if (!empty($request->all())) {
          $validator = Validator::make($request->all(), [
            'examinationType'  => 'required|unique:examinationMaster,examinationTypeId,'.',examinationMasterId,deletedFlag,0',
        ],[
            'examinationType.required'  => 'ExaminationType is mandatory.', 
            'examinationType.unique'    => 'The  ExaminationType has already been taken..', 
          
        ]); 
        if ($validator->fails()) {
            $errors = $validator->errors();
            $msg = array();
            foreach ($errors->all() as $message) {
                $msg[] = $message;
            }
            $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
        } else {
        
                $res['allExamId']    = implode(',', $arrData['examTaggingArray']);
            $obj = new ExaminationMasterModel();
            $obj->examinationTypeId = trim($arrData['examinationType']);
            $obj->classId =  $res['allExamId'];
            $obj->description = trim($arrData['description']);
            $obj->createdBy   = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0; 
           
             if($obj->save()){
                $status = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $msg = "Examination   added successfuly";
            }else {
                $statusCode = config('constant.DB_EXCEPTION_CODE');
                $msg = "Something went wrong while storing the data.";
            }
        }
    } else {
        $statusCode = config('constant.REQUEST_ERROR_CODE');
        $msg = "Something went wrong, Please try later.";
    }
    DB::commit();  
}
catch(\Throwable $t){
    Log::error("Error", [
        'Controller' => 'ExaminationMasterController',
        'Method'     => 'addExaminationMaster',
        'Error'      => $t->getMessage()
    ]);
    DB::rollback();
    $status = "ERROR";
    $statusCode = config('constant.EXCEPTION_CODE');
    $msg = config('constant.EXCEPTION_MESSAGE');      
}
    return response()->json([
        "status" => $status,
        "statusCode" => $statusCode,
        "msg" => $msg
    ], $statusCode);
}

//* Created By  :  Nitish Nanda ||  Created On  : 22-06-2022 || Component Name :ExaminationMaster || Description:view ExaminationMaster */
public function viewExaminationMaster(Request $request){
    $msg = '';
    try{
    $statusCode = config('constant.SUCCESS_CODE');
    $queryArr = DB::table('examinationMaster as em')
    ->leftJoin('esk_master.annexture as class',DB::raw("FIND_IN_SET(class.anxtValue,em.classId)"),">",DB::raw("'0'"))
    
    ->where('class.anxtType', 'CLASS_TYPE')
    ->where('class.deletedFlag', 0);
    
    if (!empty($request->input("examinationTypeId"))) {
        $queryArr =  $queryArr->where('em.examinationTypeId', trim($request->input("examinationTypeId")));
    }    
    $queryArr = $queryArr->groupBy('em.examinationTypeId','em.examinationMasterId')
    ->selectRaw("group_concat(class.anxtName) as classNames,em.examinationTypeId,em.examinationMasterId, em.description")
    
    ->where('em.deletedFlag', 0)->get();
    
    $responseData = $queryArr;
    if($responseData->isEmpty()){
        $msg = 'No record found.';
    }else{
        $response = array();
        foreach($responseData  as $res){            
            $resp['encId']       = Crypt::encryptString($res->examinationMasterId);
            $resp['classNames']     = $res->classNames;
            $resp['description']     = $res->description;
           if($res->examinationTypeId == 1){
            $resp['examinationTypeId'] ="First Term";
           }
           elseif($res->examinationTypeId == 2){
            $resp['examinationTypeId'] ="Second Term";
           }
           elseif($res->examinationTypeId == 3){
            $resp['examinationTypeId'] ="Third Term";
           }
           
            array_push($response,$resp);
        }
        $responseData = (count($response)>0)?$response:$response[0];
    }
}
catch (\Throwable $t) {
    Log::error("Error", [
        'Controller' => 'ExaminationMasterController',
        'Method' => 'viewExaminationMaster',
        'Error'  => $t->getMessage()
    ]);
    $status     = "ERROR";
    $statusCode = config('constant.EXCEPTION_CODE');
    $msg        = config('constant.EXCEPTION_MESSAGE');               
}
    return response()->json([
        "status" => 'SUCCESS',
        "statusCode" => $statusCode,
        "msg" => $msg,
        "data" => $responseData
    ],$statusCode);

}

/* Created By  :  Nitish Nanda ||  Created On  : 23-06-2022 || Component Name :ExaminationMaster || Description:get ExaminationMaster */
public function getExaminationMaster($id = NULL) 
{
    $msg = '';
    try{
    $statusCode = config('constant.SUCCESS_CODE');
    $queryData = ExaminationMasterModel::select('examinationTypeId','classId','description')
    ->where([['deletedFlag', 0]]);
    if (!empty($id)) {
        $id = Crypt::decryptString($id);
        $queryData->where('examinationMasterId', $id);
    }
    $responseData = $queryData->get();
  if ($responseData->isEmpty()) {
        $msg = 'No record found.';
    } else {
     
        $response = array();
        foreach ($responseData as $res) {
            $res['encId']               = Crypt::encryptString($res->examinationMasterId);
            $res['selectType']            = $res->examinationTypeId;
            if ($res->examinationTypeId == 1) {
                  $res['selectTypeLabel'] = 'First Term';
            } else if ($res->examinationTypeId == 2) {
                  $res['selectTypeLabel'] = 'Second Term';
            } else if ($res->examinationTypeId == 3) {
                  $res['selectTypeLabel'] = 'Third Term';
            }else {
                  $res['selectTypeLabel'] = '';
            }
            $res['allclassId']    = array_map('intval', explode(',', $res->classId));
            $res['description']        = $res->description;
            array_push($response, $res);
        }
        $responseData = (count($response) > 1) ? $response : $response[0];
    }
} 
catch (\Throwable $t) {
    Log::error("Error", [
        'Controller' => 'ExaminationMasterController',
        'Method'     => 'getExaminationMaster',
        'Error'      => $t->getMessage()
    ]);
    $status = "ERROR";
    $statusCode = config('constant.EXCEPTION_CODE');
    $msg = config('constant.EXCEPTION_MESSAGE');              
}
    return response()->json([
        "status" => 'SUCCESS',
        "statusCode" => $statusCode,
        "msg" => $msg,
        "data" => $responseData
    ], $statusCode);
}
/* Created By  :  Nitish Nanda ||  Created On  : 23-06-2022 || Component Name : updateExaminationMaster || Description: updateExaminationMaster */
public function updateExaminationMaster(Request $request)
{
     $status = "ERROR";
     DB::beginTransaction();
    try{
         $arrData = $request->all();
        $id  = Crypt::decryptString($request->input("encId"));
         if (!empty($request->all())) {
            $validator = Validator::make($arrData, [
                'selectType'  => 'required',
                 'selectType.required'  => 'selectType is mandatory.',  
           ]);
                if ($validator->fails()) {
                $errors = $validator->errors();
                    $msg = array();
            foreach ($errors->all() as $message) {
                $msg[] = $message;
            }
            $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
        } else { 
           
             $examinationTypeId=$request->input("selectType");
             $dataArr['updatedOn']           = Carbon::now('Asia/Kolkata');
             $dataArr['updatedBy']           = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
            $upObj = ExaminationMasterModel::where('examinationTypeId',$examinationTypeId)->delete();
            if ($upObj) {
                
                $dataArr['examinationTypeId'] =  $examinationTypeId;
                $dataArr['description'] =  $request->input('description');
                $dataArr['classId'] =  implode(',',$request->input('examTaggingArray') );
                $dataArr['updatedBy'] =1;
               
                if (ExaminationMasterModel::insert($dataArr)) {
                    $status = "SUCCESS";
                    $statusCode = 200;
                    $msg = "Examination  updated successfuly";
                } 
            } else {
                $statusCode = config('constant.DB_EXCEPTION_CODE');
                $msg = "Something went wrong while storing the data.";
            }
        }
    } else {
        $statusCode = config('constant.REQUEST_ERROR_CODE');
        $msg = "Something went wrong, Please try later.";
    }
    DB::commit();
}
catch(\Throwable $t){
    Log::error("Error", [
        'Controller' => 'ExaminationMasterController',
        'Method'     => 'updateExaminationMaster',
        'Error'      => $t->getMessage()
    ]);
    DB::rollback();
    $status = "ERROR";
    $statusCode = config('constant.EXCEPTION_CODE');
    $msg = config('constant.EXCEPTION_MESSAGE');      
}
    return response()->json([
        "status" => $status,
        "statusCode" => $statusCode,
        "msg" => $msg
    ], $statusCode);
}
/* Created By  :  Nitish Nanda ||  Created On  : 22-06-2022 || Component Name :ExaminationMaster || Description:delete ExaminationMaster */
public function deleteExaminationMaster(Request $request)
     {

   
     //return $id;
        $status = "ERROR";
        DB::beginTransaction();
        try{
        $id = Crypt::decryptString($request->input("encId"));
         $obj = ExaminationMasterModel::find($id);
         $dataArr['deletedFlag'] = 1;
         $dataArr['updatedOn']    = Carbon::now('Asia/Kolkata');
         $dataArr['updatedBy']   = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;

         $upObj = ExaminationMasterModel::where('examinationMasterId',$id)->update($dataArr);
         if ($upObj) {
             $status = "SUCCESS";
             $statusCode = config('constant.SUCCESS_CODE');
             $msg = "Examination Master Details deleted successfully";
             $success = true;
         } else {
             $statusCode = 402;
             $msg = "Something went wrong while deleting the data.";
             $success = false;
         }
         DB::commit();
        }
        catch(\Throwable $t){
            Log::error("Error", [
                'Controller' => 'ExaminationMasterController',
                'Method'     => 'deleteExaminationMaster',
                'Error'      => $t->getMessage()
            ]);
            DB::rollback();
            $status = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg = config('constant.EXCEPTION_MESSAGE');      
        }
         return response()->json([
             "success" => $status,
             "statusCode" => $statusCode,
             "msg" => $msg,
             'success'=>$success
         ], $statusCode);
     }
     
     public function getClassAccordingToExamType(Request $request) 
     {
        //return $id;
         $msg = '';
         $statusCode = config('constant.SUCCESS_CODE');
         $queryData = ExaminationMasterModel::select('examinationTypeId','classId','description')
         ->where([['deletedFlag', 0]]);
         if (!empty($request->input("examtype"))) {
             $queryData->where('examinationTypeId',$request->input("examtype"));
         }
         $responseData = $queryData->get();
       if ($responseData->isEmpty()) {
             $msg = 'No record found.';
         } else {
             $response = array();
             foreach ($responseData as $res) {
               
                 $res['allclassId']              = array_map('intval', explode(',', $res->classId));
    
                 array_push($response, $res);
             }
             $responseData = (count($response) > 1) ? $response : $response[0];
         }
         
         return response()->json([
             "status" => 'SUCCESS',
             "statusCode" => $statusCode,
             "msg" => $msg,
             "data" => $responseData
         ], $statusCode);
        }
  
 
}
