<?php
/**
 * Created By  : Nitish Nanda
 * Created On  : 13-06-2022
 * Module Name : Master Module
 * Description : Manage event Master Add,View,Edit,Delete
 **/

namespace App\Http\Controllers;
use App\Models\EventTypeModel;
use App\Models\EventMasterModel;
use App\Models\EventCategoryModel;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;


class EventMasterController extends Controller
{
    
 /* Created By  :  Nitish Nanda ||  Created On  : 13-06-2022 || Component Name :EventMaster || Description:get EventType */
    public function getEventName(Request $request) 
{
    $msg = '';
    try{
    $statusCode = config('constant.SUCCESS_CODE');
    // $queryData = EventTypeModel::select('eventId','eventType','createdOn')
    // ->where('deletedFlag', 0);
    $queryData = EventTypeModel::select('eventId','eventType','createdOn')->where('deletedFlag', 0);
    if (!empty($request->input("id"))) {
        $id  = Crypt::decryptString($request->input("id"));
        $queryData->where([['eventId', $id]]);
    }
    $responseData = $queryData->get();
    if ($responseData->isEmpty()) {
        $msg = 'No record found.';
    } else {
        $response = array();
        foreach ($responseData as $res) {
            $res['encId']               = Crypt::encryptString($res->eventId);
            $res['eventId']               = $res->eventId;
            $res['eventType']           = $res->eventType;
            $res['createdOn']               = $res->createdOn;
            array_push($response, $res);
        }
        $responseData = (count($response) > 1) ? $response : $response[0];
    }
} 
catch (\Throwable $t) {
    Log::error("Error", [
        'Controller' => 'EventMasterController',
        'Method'     => 'getEventName',
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
 /* Created By  :  Nitish Nanda ||  Created On  : 14-06-2022 || Component Name :EventMaster || Description:get EventCategory */
 public function getEventCategoryName(Request $request) 
 {
     $msg = '';
     try{
     $statusCode = config('constant.SUCCESS_CODE');
     // $queryData = EventTypeModel::select('eventId','eventType','createdOn')
     // ->where('deletedFlag', 0);
     $queryData = EventCategoryModel::select('eventCategoryId','categoryName','createdOn')->where('deletedFlag', 0);
     if (!empty($request->input("id"))) {
        $id  = $request->input("id");
        $queryData->where('eventId', $id);
    }
     $responseData = $queryData->get();
     if ($responseData->isEmpty()) {
         $msg = 'No record found.';
     } else {
         $response = array();
         foreach ($responseData as $res) {
             $resp['encId']               = Crypt::encryptString($res->eventCategoryId);
             $resp['eventCategoryId']               = $res->eventCategoryId;
             $resp['categoryName']           = $res->categoryName;
             $resp['createdOn']               = $res->createdOn;
             array_push($response, $resp);
         }
         $responseData =  $response ;
     }
    } 
    catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'EventMasterController',
            'Method'     => 'getEventCategoryName',
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
 /* Created By  :  Nitish Nanda ||  Created On  : 14-06-2022 || Component Name :EventMaster || Description:Add EventMaster */
 public function addEventMaster(Request $request){
        
    $status = "ERROR";
    DB::beginTransaction();
    try{
    $arrData = $request->all();
     if (!empty($request->all())) {
        $validator = Validator::make($arrData, [
            'period'  => 'required',
             'eventType'  => 'required',
            'categoryName'=>'required',
            // 'eventName' =>'required|max:40|unique:eventMaster,eventName,'.',eventMasterId,period,' .$request->input("period").',eventId,' .$request->input("eventType").',categoryId,' .$request->input("categoryName").',deletedFlag,0',
            'eventName' =>'required|max:40|unique:eventMaster,eventName,'.',eventMasterId,period,'.$request->input("period").',eventId,' .$request->input("eventType").',categoryId,' .$request->input("categoryName").',deletedFlag,0',

                'period.required'  => 'period is mandatory.',  
             'eventType.required'  => 'EventType is mandatory.',
             'categoryName.required' =>'category name is mandatory',
             'eventName.required'  => 'eventName is mandatory.',
             ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $msg = array();
            foreach ($errors->all() as $message) {
                $msg[] = $message;
            }
            $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
        } else {
            $obj = new EventMasterModel();
            $obj->period = trim($arrData['period']);
            $obj->eventId = trim($arrData['eventType']);
            $obj->categoryId = trim($arrData['categoryName']);
            $obj->eventName = trim($arrData['eventName']);
            $obj->description = trim($arrData['description']);
            $obj->createdBy   = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0; 
          if($obj->save()){
                $status = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $msg = "EventMaster added successfuly";
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
        'Controller' => 'EventMasterController',
        'Method'     => 'addEventMaster',
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

/* Created By  :  Nitish Nanda ||  Created On  : 14-06-2022 || Component Name :EventMaster || Description:view EventMaster */
public function viewEventMaster(Request $request){


    $msg = '';
    $responseData   = ''; 
    try{
        $status = "ERROR";
    $statusCode = config('constant.SUCCESS_CODE');
    $serviceType= $request->input("serviceType");
    $queryData = DB::table('eventMaster as em')
    ->leftjoin('eventType as et',function ($join){
        $join->on('et.eventId', '=', 'em.eventId')
        ->where('et.deletedFlag',0);
    })
    ->leftjoin('eventCategory as ec',function ($join){
        $join->on('ec.eventCategoryId', '=', 'em.categoryId')
        ->where('ec.deletedFlag',0);
    })
    ->where('em.deletedFlag',0)
    ->selectRaw(" em.eventMasterId,et.eventType,ec.categoryName,em.period,em.eventName,em.description");
    if (!empty($request->input("period"))) {
        $queryData->where('period', 'like', '%' . trim($request->input("period")) . '%');
    }
    if(!empty($request->input("eventName"))){
        $queryData->where([['eventName',trim($request->input("eventName"))]]);
    }
    if (!empty($request->input("description"))) {
        $queryData->where('description', trim($request->input("description")));
    }
  $totalRecord = $queryData->count();

    if($serviceType != "Download"){
                $offset         = (int)$request->input("offset") ? (int)$request->input("offset") : 0;
                $limit          = (int)$request->input("limit") ? (int)$request->input("limit") : $totalRecord;
                $queryData      = $queryData->offset($offset)->limit($limit);
            }
   // $responseData = $queryData->offset($offset)->limit($limit)->orderBy('eventName', 'ASC')->get(); 
   $responseData   = $queryData->orderBy('eventName', 'ASC')->get(); 

   if($serviceType == "Download"){  
    $userId = Crypt::decryptString($request->input("userId"));
   $downloadResponse = $this->downloadEventMasterList($responseData, $userId);

   if($downloadResponse['statusCode'] == 200){                    
       $responseData   = $downloadResponse['data'];  
       $status         = "SUCCESS";
       $statusCode     = config('constant.SUCCESS_CODE');
   } else {
       $responseData   = "";
       $msg = 'Could not create and download file.';
   }
} 
else{
    if ($responseData->isEmpty()) {
        $msg = 'No record found.'; 
                      
    } else {
        $i = $offset;
            foreach ($responseData as $key => $value) {
                $responseData[$key]->slNo = ++$i;
                $responseData[$key]->encId = Crypt::encryptString($value->eventMasterId);
                $responseData[$key]->period = 
                 ($value->period == 1) ? 'Annually':(($value->period == 2)?'Half Yearly':'Monthly');
            }
        
            $status     = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
    }
    
   }
}
    catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'EventMasterController',
            'Method' => 'viewEventMaster',
            'Error'  => $t->getMessage()
        ]);
        $status     = "ERROR";
        $statusCode = config('constant.EXCEPTION_CODE');
        $msg        = config('constant.EXCEPTION_MESSAGE');               
    }
    return response()->json([
        "status" => $status,
        "statusCode" => $statusCode,
        "msg" => $msg,
        "data" => $responseData,
        "totalRecord" => $totalRecord
    ], $statusCode);
}

/* Created By : Nitish Nanda || Created On : 19-08-2022 || Service method Name : downloadEventMasterList || Description: downloadEventMasterList data  */
private function downloadEventMasterList($getCsvData, $userId)
{ 
    $status     = "ERROR";
    $statusCode = config('constant.EXCEPTION_CODE');
    $msg        = '';
    $responseData = '';  
    try { 
        $csvFileName = "EventMaster_List_".$userId."_".time().".csv";
        $csvColumns = ["Sl. No", "period", "Event Type","Event Category","Event Name","Description"];

        $csvDataArr = array();
        $slno = 1;
        foreach($getCsvData as $csvData){
             //For Period Name
             if($csvData->period == 1){
                $periodName ="Annualy";
            }
            else if($csvData->period == 2){
                $periodName = 'Half Yearly';
            }else if($csvData->period == 3){
                $periodName = 'Monthly';
            }
            else{
                $periodName  = '';
            }
            //End
            $csvDataArr[] = [
                $slno,
                !empty($periodName) ? $periodName : '--',
                !empty($csvData->eventType) ? $csvData->eventType : '--',
                !empty($csvData->categoryName) ? $csvData->categoryName : '--',
                !empty($csvData->eventName) ? $csvData->eventName : '--',
                !empty($csvData->description) ? $csvData->description : '--',
              
                
            ];
            $slno++;
        }
        
        $downloadResponse = $this->downloadCsv($csvFileName, $csvColumns, $csvDataArr);
        // return $downloadResponse;
        if($downloadResponse['statusCode'] == 200){
            $status = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
            $responseData = $downloadResponse['data'];  
        } else {
            $msg = 'Could not create and download file.';
        }
    } catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'EventMasterController',
            'Method' => 'downloadEventMasterList',
            'Error'  => $t->getMessage()
        ]);
        $status = "ERROR";
        $statusCode = config('constant.EXCEPTION_CODE');
        $msg = config('constant.EXCEPTION_MESSAGE');           
    }

    return [
        "status"     => $status,
        "statusCode" => $statusCode,
        "msg"        => $msg,
        "data"       => $responseData
    ];    
}
/* Created By  :  Nitish Nanda ||  Created On  : 14-06-2022 || Component Name :EventCategory || Description:get EventMaster */
public function getEventMaster(Request $req) 
{
    $msg = '';
    try{
    $statusCode = config('constant.SUCCESS_CODE');
    $queryData = EventMasterModel::select('eventMasterId','period','eventId','categoryId','eventName','description','createdOn')
    ->where([['deletedFlag', 0]]);
    if (!empty($req->input("id"))) {
        $id  = Crypt::decryptString($req->input("id"));
        $queryData->where([['eventMasterId', $id]]);
    } $responseData = $queryData->get();
  if ($responseData->isEmpty()) {
        $msg = 'No record found.';
    } else {
     
        $response = array();
        foreach ($responseData as $res) {
            $res['encId']               = Crypt::encryptString($res->eventMasterId);
            $res['period']            = $res->period;
             $res['eventId']            = $res->eventId;
             $res['categoryId']       = $res->categoryId;
             $res['eventName']       = $res->eventName;
             $res['description']        = $res->description;
             $res['createdOn']          = $res->createdOn;
            array_push($response, $res);
        }
        $responseData = (count($response) > 1) ? $response : $response[0];
    }
}
catch (\Throwable $t) {
    Log::error("Error", [
        'Controller' => 'EventMasterController',
        'Method'     => 'getEventMaster',
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
/* Created By  :  Nitish Nanda ||  Created On  : 14-06-2022 || Component Name :eventMaster || Description:update Eventmaster */  
public function updateEventMaster(Request $request)
{
    
    $status = "ERROR";
    DB::beginTransaction();
    try{
    $arrData = $request->all();
    $id  = Crypt::decryptString($request->input("encId"));
    if (!empty($request->all())) {
        $validator = Validator::make($arrData, [
            'period'  => 'required',
             'eventType'  => 'required',
            'categoryName'=>'required',
            'eventName' =>'required|max:40|unique:eventMaster,eventName,'.$id.',eventMasterId,period,'.$request->input("period").',eventId,' .$request->input("eventType").',categoryId,' .$request->input("categoryName").',deletedFlag,0',
             ], [
                'period.required'  => 'period is mandatory.',  
             'eventType.required'  => 'EventType is mandatory.',
             'categoryName.required' =>'category name is mandatory',
             'eventName.required'  => 'eventName is mandatory.',
             ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            $msg = array();
            foreach ($errors->all() as $message) {
                $msg[] = $message;
            }
            $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
        } else { 
            $dataArr['period']      = trim($arrData['period']);
            $dataArr['eventId']      = trim($arrData['eventType']);
            $dataArr['categoryId']      = trim($arrData['categoryName']);
            $dataArr['eventName']      = trim($arrData['eventName']);
            $dataArr['description']      = trim($arrData['description']);
            $dataArr['updatedOn']           = Carbon::now('Asia/Kolkata');
            $dataArr['updatedBy']           = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
             $upObj = EventMasterModel::where('eventMasterId',$id)->update($dataArr);
          if ($upObj) {
                $status = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $msg = "Event Master Updated successfuly";
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
        'Controller' => 'EventMasterController',
        'Method'     => 'updateEventMaster',
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


/* Created By  :  Nitish Nanda ||  Created On  : 08-06-2022 || Component Name :EventCategory || Description:delete EventCategory */
public function deleteEventMaster(Request $request)
     {
         $status = "ERROR";
         DB::beginTransaction();
         try{
         $id = Crypt::decryptString($request->input("encId"));
         $obj = EventMasterModel::find($id);
         $dataArr['deletedFlag'] = 1;
         $dataArr['updatedOn']    = Carbon::now('Asia/Kolkata');
         $dataArr['updatedBy']   = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;

         $upObj = EventMasterModel::where('eventMasterId',$id)->update($dataArr);
         if ($upObj) {
             $status = "SUCCESS";
             $statusCode = config('constant.SUCCESS_CODE');
             $msg = "Event Master Details deleted successfully";
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
                'Controller' => 'EventMasterController',
                'Method'     => 'deleteEventMaster',
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

  /* Created By  :  Nitish Nanda ||  Created On  : 21-06-2022 || Component Name :EventCategory || Description:get EventName */   

 public function eventName(Request $request) 
{
    $msg = '';
    try{
    $statusCode = config('constant.SUCCESS_CODE');
    $queryData = EventMasterModel::select('eventMasterId','eventName','createdOn')
    ->where('deletedFlag', 0);

    if (!empty($request->input("id"))) {
        $id  = Crypt::decryptString($request->input("id"));
        $queryData->where('eventMasterId', $id);

    }
    $responseData = $queryData->get();
  if ($responseData->isEmpty()) {
        $msg = 'No record found.';
    } else {
     
        $response = array();
        foreach ($responseData as $res) {
             $res['encId']               = Crypt::encryptString($res->eventMasterId);
             $res['eventName']       = $res->eventName;
             $res['createdOn']          = $res->createdOn;
            array_push($response, $res);
        }
        $responseData =$response;
    }

}
catch (\Throwable $t) {
    Log::error("Error", [
        'Controller' => 'EventMasterController',
        'Method'     => 'eventName',
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
}
