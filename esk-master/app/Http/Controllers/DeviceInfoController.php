<?php
/**
 * Created By  : Nitish Nanda
 * Created On  : 23-05-2022
 * Module Name : Master Module
 * Description : Manage Device Information Add, View, Update, Delete.
 * modified By: saubhagya ranjan patra
 * Modified On  : 29-07-2022
 **/

namespace App\Http\Controllers;
use App\Models\DeviceInfoModel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;


class DeviceInfoController extends Controller
{
    
      /* Created By  :  Nitish Nanda ||  Created On  : 23-05-2022 || Component Name : DeviceInformation || Description: Create DeviceInfo */
      public function addDeviceInfo(Request $req)
      {
         //return $req;
          $status = "ERROR";
          DB::beginTransaction();
          try{
          if(!empty(request()->all())) {
              $validator = Validator::make($req->all(), [
                  'districtId'   => 'required|numeric',
                  'blockId'  => 'required',
                  'clusterId'     => 'required',
                  'schoolId' => 'required',
                  'teacherId' => 'required', 
                  'deviceType'=>'required',                 
                  'receivedDate'    => 'required',
                  'uuid_imei'    => 'required|unique:deviceInformation,uuid_imei,' . ',uuid_imei,deletedFlag,0',

              ],[
                'districtId.required'    => 'District Id is mandatory.',
                 'blockId.required'      => 'Block Id is mandatory.',
                'clusterId.required'  => 'Cluster Id is mandatory.',
                'schoolId.required'  => 'School Id is mandatory.',
                'teacherId.required'  => 'Teacher Id is mandatory.',
                'deviceType.required'  => 'deviceType  is mandatory.',
                'receivedDate.required'  => 'receivedDate  is mandatory.',
                'uuid_imei.required'  => 'uuid_imei  is mandatory.',
                
            ]); 
                      
            if($validator->fails()) {
                $errors = $validator->errors();
                $msg = array();
                foreach ($errors->all() as $message) {
                    $msg[] = $message;
                }
                $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
            }else{    
                $obj = new DeviceInfoModel();
                $obj->districtId = $req->input("districtId");
                $obj->blockId = $req->input("blockId");
                $obj->clusterId = $req->input("clusterId");
                $obj->schoolId = $req->input("schoolId");
                $obj->teacherId = $req->input("teacherId");
                $obj->receivedDate = $req->input("receivedDate");
                $obj->deviceType = $req->input("deviceType");
                $obj->uuid_imei = $req->input("uuid_imei");
                $obj->createdBy  = (!empty($req->input("userId"))) ? Crypt::decryptString($req->input("userId")) : 0;
                $obj->createdon  = Carbon::now('Asia/Kolkata');
                if($obj->save()){
                    $status = "SUCCESS";
                    $statusCode = config('constant.SUCCESS_CODE');
                    $msg = "Device Information added successfuly";
                }else{
                    $statusCode = config('constant.DB_EXCEPTION_CODE');
                    $msg = "Something went wrong while storing the data.";
                }
            }
        }else{
            $statusCode = config('constant.REQUEST_ERROR_CODE');
            $msg = "Something went wrong, Please try later.";
        }
        DB::commit();
    }
    catch(\Throwable $t){
        Log::error("Error", [
            'Controller' => 'DeviceInfoController',
            'Method'     => 'addDeviceInfo',
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
        ],$statusCode);
    }


    
 /* Created By  :  Nitish Nanda ||  Created On  : 24-05-2022 || Component Name : DeviceInformation || Description: view DeviceInfo */
      public function viewDeviceInfo(Request $request) 
      { 
         $msg = '';
         $responseData   = '';    
        try{
         $statusCode = config('constant.SUCCESS_CODE');
         $serviceType= $request->input("serviceType");
          $queryData = DB::table('esk_master.deviceInformation as deviceinfo')
          -> leftJoin('esk_master.clusters as cl', function($join){
            $join->on('deviceinfo.clusterId', '=', 'cl.clusterId')->where('cl.deletedFlag','=',0);
        })
        -> leftJoin('esk_school.school as sch', function($join){
            $join->on('deviceinfo.schoolId', '=', 'sch.schoolId')->where('sch.deletedFlag','=',0);
        })
        -> leftJoin('esk_teacher.teacherProfile as tch', function($join){
            $join->on('deviceinfo.teacherId', '=', 'tch.tId')->where('tch.deletedFlag','=',0);
        })
        ->selectRaw('deviceinfo.deviceInfoId,deviceinfo.deviceType,deviceinfo.receivedDate,deviceinfo.uuid_imei,cl.districtName,cl.blockName,cl.clusterName,sch.schoolName,tch.teacherName,deviceinfo.districtId,deviceinfo.blockId,deviceinfo.clusterId');
          if (!empty($request->input("id"))) {
            $id  = Crypt::decryptString($request->input("id"));
              $queryData->where('deviceinfo.deviceInfoId', $id);
          }
          if(!empty($request->input("districtId"))){
            $queryData->where('deviceinfo.districtId',trim($request->input("districtId")));
            }
          if(!empty($request->input("blockId"))){
            $queryData->where('deviceinfo.blockId',trim($request->input("blockId")));
            }
          if(!empty($request->input("clusterId"))){
            $queryData->where('deviceinfo.clusterId',trim($request->input("clusterId")));
            }
          if(!empty($request->input("schoolId"))){
            $queryData->where('deviceinfo.schoolId',trim($request->input("schoolId")));
            }
          if(!empty($request->input("teacherId"))){
            $queryData->where('deviceinfo.teacherId',trim($request->input("teacherId")));
            }
          if(!empty($request->input("deviceType"))){
            $queryData->where('deviceinfo.deviceType',trim($request->input("deviceType")));
            }
          if(!empty($request->input("uuidImei"))){
            $queryData->where('deviceinfo.uuid_imei',trim($request->input("uuidImei")));
            }
          $queryData = $queryData-> where('deviceinfo.deletedFlag', 0);
          $totalRecord = $queryData->count();

         
        if($serviceType != "Download"){
                $offset         = (int)$request->input("offset") ? (int)$request->input("offset") : 0;
                $limit          = (int)$request->input("limit") ? (int)$request->input("limit") : $totalRecord;
                $queryData      = $queryData->offset($offset)->limit($limit);
            }
     
         // $responseData = $queryData->offset($offset)->limit($limit)->get();
          $responseData   = $queryData->get();

   
          if($serviceType == "Download"){  
            $userId = Crypt::decryptString($request->input("userId"));
           $downloadResponse = $this->downloadDeviceInfoList($responseData, $userId);

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
            //   $success = true;
          } else {
               $i = $offset;
                foreach ($responseData as $key => $value) {
                    $responseData[$key]->slNo = ++$i;
                    $responseData[$key]->encId = Crypt::encryptString($value->deviceInfoId);
                    if($value->deviceType==1){
                        $responseData[$key]->deviceTypeName ="MOBILE";
                    }else if($value->deviceType==2){
                        $responseData[$key]->deviceTypeName ="TABLET";
                    }
                    else if($value->deviceType==3){
                        $responseData[$key]->deviceTypeName ="LAPTOP";
                    }
                    else{
                        $responseData[$key]->deviceTypeName ="DESKTOP";
                    }
                }
                $status     = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                // $success = true;
           }
        }
    }
    catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'DeviceInfoController',
            'Method' => 'viewDeviceInfo',
            'Error'  => $t->getMessage()
        ]);
        $status     = "ERROR";
        $statusCode = config('constant.EXCEPTION_CODE');
        $msg        = config('constant.EXCEPTION_MESSAGE');               
    }
          return response()->json([
            //"sucess" => $success,
            "statusCode" => $statusCode,
            "msg" => $msg,
            "data" => $responseData,
            "totalRecord" => $totalRecord
        ], $statusCode);
      }

       /* Created By : Nitish Nanda || Created On : 19-08-2022 || Service method Name : downloadDeviceInfoList || Description: downloadDeviceInfoList data  */
    private function downloadDeviceInfoList($getCsvData, $userId)
    { 
        $status     = "ERROR";
        $statusCode = config('constant.EXCEPTION_CODE');
        $msg        = '';
        $responseData = '';  
        try { 
            $csvFileName = "DeviceInfo_List_".$userId."_".time().".csv";
            $csvColumns = ["Sl. No", "District", "Block","Cluster","School","Teacher","Received Date","Device Type","UUID/IMEI"];

            $csvDataArr = array();
            $slno = 1;
            foreach($getCsvData as $csvData){
                  //For Device  Name
                  if($csvData->deviceType == 1){
                    $deviceName ="MOBILE";
                }
                else if($csvData->deviceType == 2){
                    $deviceName = 'TABLET';
                }else if($csvData->deviceType == 3){
                    $deviceName = 'LAPTOP';
                }
                else if($csvData->deviceType == 4){
                    $deviceName = 'DESKTOP';
                }else{
                    $deviceName   = '';
                }
                //End

                $csvDataArr[] = [
                    $slno,
                    !empty($csvData->districtName) ? $csvData->districtName : '--',
                    !empty($csvData->blockName) ? $csvData->blockName : '--',
                    !empty($csvData->clusterName) ? $csvData->clusterName : '--',
                    !empty($csvData->schoolName) ? $csvData->schoolName : '--',
                    !empty($csvData->teacherName) ? $csvData->teacherName : '--',
                    !empty($csvData->receivedDate) ? $csvData->receivedDate : '--',
                    !empty($deviceName) ? $deviceName : '--',
                    !empty($csvData->uuid_imei) ? $csvData->uuid_imei : '--',
                    
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
                'Controller' => 'DeviceInfoController',
                'Method' => 'downloadDeviceInfoList',
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
      
      /* Created By  :  Nitish Nanda ||  Created On  : 24-05-2022 || Component Name : DeviceInformation || Description: delete DeviceInfo */
      public function deleteDevice(Request $req)
      {
         $status = "ERROR";
         $success = "";
         DB::beginTransaction();
          try{
          $id  = Crypt::decryptString($req->input("encId"));
            $dataArr['deletedFlag'] = 1;
            $dataArr['updatedBy'] = (!empty($req->input("userId"))) ? Crypt::decryptString($req->input("userId")) : 0;
            $dataArr['updatedOn'] = Carbon::now('Asia/Kolkata');
          $upObj =DeviceInfoModel ::where('deviceInfoId',$id)->update($dataArr);
          if ($upObj) {
              $status = "SUCCESS";
              $statusCode = config('constant.SUCCESS_CODE');
              $msg = "Device Information Details deleted successfully";
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
                'Controller' => 'DeviceInfoController',
                'Method'     => 'deleteDevice',
                'Error'      => $t->getMessage()
            ]);
            DB::rollback();
            $status = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg = config('constant.EXCEPTION_MESSAGE'); 
            $success=false;     
        }
          return response()->json([
            "status"     => $status,
            "statusCode" => $statusCode,
            "msg"        => $msg,
            "success"    => $success
          ], $statusCode);
      }
  /* Created By  :  Nitish Nanda ||  Created On  : 24-05-2022 || Component Name : DeviceInformation || Description: get DeviceInfo */
      public function getDeviceInfo(Request $req){
        $msg = '';
        try{
        $statusCode = config('constant.SUCCESS_CODE');
        $queryData = DeviceInfoModel::select('deviceInfoId','districtId','blockId','clusterId','schoolId','teacherId','deviceType','receivedDate','uuid_imei')
        ->where([['deletedFlag', 0]]);
        if (!empty($req->input("id"))) {
            $id  = Crypt::decryptString($req->input("id"));
            $queryData->where([['deviceInfoId', $id]]);
        }
        $responseData = $queryData->get();
        if ($responseData->isEmpty()) {
            $msg = 'No record found.';
        } else {
            $response = array();
            foreach ($responseData as $res) {
                $res['encId']               = Crypt::encryptString($res->deviceInfoId);
                $res['districtName']        = $res->district->districtName;               
                $res['blockName']        = $res->block->blockName;             
               $res['clusterName']        = $res->cluster->clusterName;
                $res['schoolId']        = $res->schoolId;
                $res['teacherId']        = $res->teacherId;
                $res['deviceType']        = $res->deviceType;
                $res['receivedDate']        = $res->receivedDate;
                $res['uuid_imei']        = $res->uuid_imei;
                array_push($response, $res);
            }
            $responseData = (count($response) > 1) ? $response : $response[0];
        }
    }
    catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'subjectcontroller',
            'Method'     => 'getDeviceInfo',
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
/* Created By  :  Nitish Nanda ||  Created On  : 24-05-2022 || Component Name : DeviceInformation || Description: update DeviceInfo */
public function updateDeviceInfo(Request $request)
{
$status = "ERROR";
DB::beginTransaction();
try{
    $arrData = $request->all();
     $id  = Crypt::decryptString($arrData['encId']);
 if (!empty($request->all())) {
    $validator = Validator::make($request->all(), [
        'districtId'=> 'required',
        'blockId'  => 'required',
        'clusterId'=> 'required',
        'schoolId' => 'required',
        'teacherId' => 'required',                  
        'deviceType'=>'required',
        'receivedDate'=> 'required',
        'uuid_imei'=> 'required|unique:deviceInformation,uuid_imei,' . $id . ',uuid_imei,deletedFlag,0',
    ],[
      'districtId.required' => 'District Id is mandatory.',
       'blockId.required'  => 'Block Id is mandatory.',
      'clusterId.required' => 'Cluster Id is mandatory.',
      'schoolId.required' => 'School Id is mandatory.',
      'teacherId.required' => 'Teacher Id is mandatory.',
      'deviceType.required' => 'deviceType  is mandatory.',
      'receivedDate.required' => 'receivedDate  is mandatory.',
      'uuid_imei.required' => 'uuid_imei  is mandatory.',  
  ]); 
        if ($validator->fails()) { 
          
            $errors = $validator->errors();
            $msg = array();
            foreach ($errors->all() as $message) {
                $msg[] = $message;
            }
            $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
        } else {
            $id  = Crypt::decryptString($request->input("encId"));
            $dataArr['districtId']= trim($arrData['districtId']);
            $dataArr['blockId'] = trim($arrData['blockId']);
            $dataArr['clusterId']= trim($arrData['clusterId']);
            $dataArr['schoolId'] = trim($arrData['schoolId']);
            $dataArr['teacherId'] = trim($arrData['teacherId']);
            $dataArr['receivedDate']= trim($arrData['receivedDate']);
            $dataArr['deviceType'] = trim($arrData['deviceType']);
            $dataArr['uuid_imei'] = trim($arrData['uuid_imei']);
            $dataArr['updatedBy'] = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
            $dataArr['updatedOn'] = Carbon::now('Asia/Kolkata');
         try {
                $upObj = DeviceInfoModel::where('deviceInfoId',$id)->update($dataArr);
                $status = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $msg = "Device Information Updated successfuly";
            } catch (\Illuminate\Database\QueryException $e) {
                $statusCode = config('constant.DB_EXCEPTION_CODE');
                $msg = "Something went wrong while storing the data.";
            }


            if ($upObj) {
                $status = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $msg = "DeviceInfo Updated successfuly";
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
        'Controller' => 'DeviceInfoController',
        'Method'     => 'updateDeviceInfo',
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
/* Created By  :  Saubhgya Ranjan Patra ||  Created On  : 09-05-2022 || Service Method Name : getTeacherAccordingToSchool || Description: get teacher list accord to school id  */
public function getTeacherAccordingToSchool(Request $req)
{
    $msg = '';
    $statusCode = config('constant.SUCCESS_CODE');
    try{
    $queryData  = DB::table('esk_teacher.teacherProfile')->select('tId','teacherName','schoolId')->where('deletedFlag',0);
    if(!empty($req->input("schoolId"))){
        $queryData->where('schoolId',trim($req->input("schoolId")));
    }
    $queryData = $queryData->orderBy('teacherName', 'ASC');
    $response = $queryData->get();
    //return $responseData;
    if($response->isEmpty()){
        $msg = 'No record found.';
    }else{
       $responseData  =  $response;
    }
    }catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'DeviceInfoController',
            'Method'     => 'getTeacherAccordingToSchool',
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
    ],$statusCode);
}

}
