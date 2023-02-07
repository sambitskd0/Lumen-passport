<?php
/**
 * Created By  : Nitish Nanda
 * Created On  : 08-06-2022
 * Module Name : Master Module
 * Description : Manage event Category Add,View,Edit,Delete
 **/

namespace App\Http\Controllers;
use App\Models\EventTypeModel;
use App\Models\EventCategoryModel;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EventCategoryController extends Controller
{
    /* Created By  :  Nitish Nanda ||  Created On  : 08-06-2022 || Component Name :EventCategory || Description:get EventType */
    public function getEvent(Request $request) 
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
        'Controller' => 'EventCategoryController',
        'Method'     => 'getEvent',
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

 /* Created By  :  Nitish Nanda ||  Created On  : 08-06-2022 || Component Name :EventCategory || Description:Add EventCategory */
 public function addEventCategory(Request $request){
        
    $status = "ERROR";
    DB::beginTransaction();
    try{
    $arrData = $request->all();
    
    if (!empty($request->all())) {
        $validator = Validator::make($arrData, [
            'eventType'  => 'required|unique:eventCategory,eventId,'.',eventCategoryId,deletedFlag,0',
            'categoryName'=>'required'
            ], [
            'eventType.required'  => 'EventType is mandatory.',
            'categoryName.required' =>'category name is mandatory',
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
            $obj = new EventCategoryModel();
            $obj->eventId = trim($arrData['eventType']);
            $obj->categoryName = trim($arrData['categoryName']);
            $obj->description = trim($arrData['description']);
            $obj->createdBy   = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0; 
          if($obj->save()){
                $status = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $msg = "Event Category Added Successfuly";
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
        'Controller' => 'EventCategoryController',
        'Method'     => 'addEventCategory',
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

/* Created By  :  Nitish Nanda ||  Created On  : 08-06-2022 || Component Name :EventCategory || Description:view EventCategory */
public function viewEventCategory(Request $request){
    
    $msg = '';
    $responseData   = '';  
   
    try{
     $status = "ERROR";
    $statusCode = config('constant.SUCCESS_CODE');
    $serviceType= $request->input("serviceType");
    $queryData = DB::table('eventCategory as ec')
    ->leftjoin('eventType as et',function ($join){
        $join->on('et.eventId', '=', 'ec.eventId')
        ->where('et.deletedFlag',0);
    })
    ->where('ec.deletedFlag',0)
    ->selectRaw(" ec.eventCategoryId,ec.categoryName,ec.description,et.eventType");
 if (!empty($request->input("categoryName"))) {
        $queryData->where('categoryName', 'like', '%' . trim($request->input("categoryName")) . '%');
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
    //$responseData = $queryData->offset($offset)->limit($limit)->orderBy('categoryName', 'ASC')->get(); 
    $responseData   = $queryData->orderBy('categoryName', 'ASC')->get();
    if($serviceType == "Download"){  
        $userId = Crypt::decryptString($request->input("userId"));
       $downloadResponse = $this->downloadEventCatList($responseData, $userId);

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
                $responseData[$key]->encId = Crypt::encryptString($value->eventCategoryId);
            }
            
            $status     = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
    }
    
   }
}
    catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'EventCategoryController',
            'Method' => 'viewEventCategory',
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

/* Created By : Nitish Nanda || Created On : 19-08-2022 || Service method Name : downloadEventCatList || Description: downloadEventCatList data  */
private function downloadEventCatList($getCsvData, $userId)
{ 
    $status     = "ERROR";
    $statusCode = config('constant.EXCEPTION_CODE');
    $msg        = '';
    $responseData = '';  
    try { 
        $csvFileName = "EventCat_List_".$userId."_".time().".csv";
        $csvColumns = ["Sl. No", "Event Type","Event Name", "District Code"];

        $csvDataArr = array();
        $slno = 1;
        foreach($getCsvData as $csvData){
            $csvDataArr[] = [
                $slno,
                !empty($csvData->eventType) ? $csvData->eventType : '--',
                !empty($csvData->categoryName) ? $csvData->categoryName : '--',
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
            'Controller' => 'EventCategoryController',
            'Method' => 'downloadEventCatList',
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
/* Created By  :  Nitish Nanda ||  Created On  : 08-06-2022 || Component Name :EventCategory || Description:get EventCategory */
public function getEventCategory(Request $request) 
{
    $msg = '';
    try{
    $statusCode = config('constant.SUCCESS_CODE');
    $queryData = EventCategoryModel::select('eventCategoryId','eventId','categoryName','description','createdOn')
    ->where([['deletedFlag', 0]]);
    if (!empty($request->input("id"))) {
        $id  = Crypt::decryptString($request->input("id"));
        $queryData->where([['eventCategoryId', $id]]);
    }
    $responseData = $queryData->get();
  if ($responseData->isEmpty()) {
        $msg = 'No record found.';
    } else {
     
        $response = array();
        foreach ($responseData as $res) {
            $res['encId']               = Crypt::encryptString($res->eventCategoryId);
             $res['eventId']            = $res->eventId;
             $res['categoryName']       = $res->categoryName;
             $res['description']        = $res->description;
             $res['createdOn']          = $res->createdOn;
            array_push($response, $res);
        }
        $responseData = (count($response) > 1) ? $response : $response[0];
    }
}
catch (\Throwable $t) {
    Log::error("Error", [
        'Controller' => 'EventCategoryController',
        'Method'     => 'getEventCategory',
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
public function updateEventCategory(Request $request)
{
    
    $status = "ERROR";
    DB::beginTransaction();
    try{
    $arrData = $request->all();
    $id  = Crypt::decryptString($request->input("encId"));
    if (!empty($request->all())) {
        $validator = Validator::make($arrData, [
            'eventType'  => 'required|unique:eventCategory,eventId,'.$id.',eventCategoryId,deletedFlag,0',
            'categoryName'=>'required'
            ], [
            'eventType.required'  => 'EventType is mandatory.',
            'categoryName.required' =>'category name is mandatory',
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
           
            $dataArr['eventId']      = trim($arrData['eventType']);
            $dataArr['categoryName']      = trim($arrData['categoryName']);
            $dataArr['description']      = trim($arrData['description']);
            $dataArr['updatedOn']           = Carbon::now('Asia/Kolkata');
            $dataArr['updatedBy']           = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
             $upObj = EventCategoryModel::where('eventCategoryId',$id)->update($dataArr);
          if ($upObj) {
                $status = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $msg = "Event Category Updated Successfuly";
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
        'Controller' => 'EventCategoryController',
        'Method'     => 'updateEventCategory',
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
public function deleteEventCategory(Request $request)
     {
         $status = "ERROR";
         DB::beginTransaction();
         try{
         $id = Crypt::decryptString($request->input("encId"));
         $obj = EventCategoryModel::find($id);
         $dataArr['deletedFlag'] = 1;
         $dataArr['updatedOn']    = Carbon::now('Asia/Kolkata');
     $dataArr['updatedBy']   = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;


         $upObj = EventCategoryModel::where('eventCategoryId',$id)->update($dataArr);
         if ($upObj) {
             $status = "SUCCESS";
             $statusCode = config('constant.SUCCESS_CODE');
             $msg = "Event Category Details Deleted Successfully";
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
                'Controller' => 'EventCategoryController',
                'Method'     => 'updateEventCategory',
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


