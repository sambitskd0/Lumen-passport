<?php
/**
 * Created By  : Nitish Nanda
 * Created On  : 07-06-2022
 * Module Name : Master Module
 * Description : Manage event type Add,View,Edit,Delete
 **/

namespace App\Http\Controllers;
use App\Models\EventTypeModel;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
class EventTypeController extends Controller
{
    //
     /* Created By  :  Nitish Nanda ||  Created On  : 07-06-2022 || Component Name :EventType || Description:Add EventType */
     public function addEventType(Request $request){
        
        $status = "ERROR";
        DB::beginTransaction();
        try{
        $arrData = $request->all();
        
        if (!empty($request->all())) {
            $validator = Validator::make($arrData, [
                'eventType'  => 'required|unique:eventType,eventType,'.',eventId,deletedFlag,0|max:20',
                ], [
                'eventType.required'  => 'Event Type is mandatory.',
                'eventType.unique'    => 'The Event type has already been taken..',
                
                 ]);
  if ($validator->fails()) {
                $errors = $validator->errors();
                $msg = array();
                foreach ($errors->all() as $message) {
                    $msg[] = $message;
                }
                $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
            } else {
                $obj = new EventTypeModel();
                $obj->eventType = trim($arrData['eventType']);
                $obj->description = trim($arrData['description']);
                $obj->createdBy   = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0; 
              if($obj->save()){
                    $status = "SUCCESS";
                    $statusCode = config('constant.SUCCESS_CODE');
                    $msg = "Event Type added successfuly";
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
            'Controller' => 'EventTypeController',
            'Method'     => 'addEventType',
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

 /* Created By  :  Nitish Nanda ||  Created On  : 07-06-2022 || Component Name :ShiftMaster || Description:view eventType */
 public function viewEventType(Request $request){
    $msg = '';
    $responseData   = '';    
        
    try{
        $status = "ERROR";
    $statusCode = config('constant.SUCCESS_CODE');
    $serviceType= $request->input("serviceType");
    $queryData = EventTypeModel::select('eventId','eventType','description')->where('deletedflag', 0);   
    if (!empty($request->input("eventId"))) {
        $queryData->where('eventId', trim($request->input("eventId")));
    }
    if (!empty($request->input("eventType"))) {
        $queryData->where('eventType', 'like', '%' . trim($request->input("eventType")) . '%');
    }
    if (!empty($request->input("description"))) {
        $queryData->where('description', trim($request->input("description")));
    }
    $queryData = $queryData->where('deletedflag',0);
    $totalRecord = $queryData->count();
      if($serviceType != "Download"){
        $offset         = (int)$request->input("offset") ? (int)$request->input("offset") : 0;
        $limit          = (int)$request->input("limit") ? (int)$request->input("limit") : $totalRecord;
        $queryData      = $queryData->offset($offset)->limit($limit);
    }
    //$responseData = $queryData->offset($offset)->limit($limit)->orderBy('eventType', 'ASC')->get();
    $responseData   = $queryData->orderBy('eventType', 'ASC')->get();
    if($serviceType == "Download"){  
        $userId = Crypt::decryptString($request->input("userId"));
       $downloadResponse = $this->downloadEventTypeList($responseData, $userId);

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
                $responseData[$key]->encId = Crypt::encryptString($value->eventId);
            }
            $status     = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
           
    }
    
   }
}
    catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'EventTypeController',
            'Method' => 'viewEventType',
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

 /* Created By : Nitish Nanda || Created On : 19-08-2022 || Service method Name : downloadEventTypeList || Description: downloadEventTypeList data  */
 private function downloadEventTypeList($getCsvData, $userId)
 { 
     $status     = "ERROR";
     $statusCode = config('constant.EXCEPTION_CODE');
     $msg        = '';
     $responseData = '';  
     try { 
         $csvFileName = "EventType_List_".$userId."_".time().".csv";
         $csvColumns = ["Sl. No", "EventType", "Description"];

         $csvDataArr = array();
         $slno = 1;
         foreach($getCsvData as $csvData){
             $csvDataArr[] = [
                 $slno,
                 !empty($csvData->eventType) ? $csvData->eventType : '--',
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
             'Controller' => 'EventTypeController',
             'Method' => 'downloadEventTypeList',
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
/* Created By  :  Nitish Nanda ||  Created On  : 07-06-2022 || Component Name :ShiftMaster || Description:get EventType */
public function getEventType(Request $request) 
{
    $msg = '';
    try{
    $statusCode = config('constant.SUCCESS_CODE');
    $queryData = EventTypeModel::select('eventId','eventType','description','createdOn')
    ->where([['deletedFlag', 0]]);
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
            $res['eventType']        = $res->eventType;
            $res['description']        = $res->description;
         $res['createdOn']               = $res->createdOn;
            array_push($response, $res);
        }
        $responseData = (count($response) > 1) ? $response : $response[0];
    }
}
catch (\Throwable $t) {
    Log::error("Error", [
        'Controller' => 'EventTypeController',
        'Method'     => 'getEventType',
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
/* Created By  :  Nitish Nanda ||  Created On  : 07-06-2022 || Component Name :ShiftMaster || Description:update EventType */  
public function updateEventType(Request $request)
{
    
    $status = "ERROR";
    DB::beginTransaction();
    try{
    $arrData = $request->all();
    $id  = Crypt::decryptString($request->input("encId"));
    if (!empty($request->all())) {
        $validator = Validator::make($arrData, [
            'eventType'  => 'required|unique:eventType,eventType,'.$id.',eventId,deletedFlag,0',
            ], [
            'eventType.required'  => 'eventType is mandatory.',
            'eventType.unique'    => 'The Event type has already been taken..',
            ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $msg = array();
            foreach ($errors->all() as $message) {
                $msg[] = $message;
            }
            $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
        } else { 
           
            $dataArr['eventType']      = trim($arrData['eventType']);
            $dataArr['description']      = trim($arrData['description']);
            $dataArr['updatedOn']           = Carbon::now('Asia/Kolkata');
            $dataArr['updatedBy']           = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
             $upObj = EventTypeModel::where('eventId',$id)->update($dataArr);
          if ($upObj) {
                $status = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $msg = "Event Type Updated successfuly";
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
        'Controller' => 'EventTypeController',
        'Method'     => 'updateEventType',
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


/* Created By  :  Nitish Nanda ||  Created On  : 07-06-2022 || Component Name :ShiftMaster || Description:delete eventType */

public function deleteEventType(Request $request)
     {
           $status = "ERROR";
           DB::beginTransaction();
           try{
         $id = Crypt::decryptString($request->input("encId"));
         $obj = EventTypeModel::find($id);
         $dataArr['deletedFlag'] = 1;
         $dataArr['updatedOn']    = Carbon::now('Asia/Kolkata');
        $dataArr['updatedBy']   = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;

         $upObj = EventTypeModel::where('eventId',$id)->update($dataArr);
         if ($upObj) {
             $status = "SUCCESS";
             $statusCode = config('constant.SUCCESS_CODE');
             $msg = "Event Type Details deleted successfully";
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
                'Controller' => 'EventTypeController',
                'Method'     => 'deleteEventType',
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
}

