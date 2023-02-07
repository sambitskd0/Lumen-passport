<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotificationCategoryModel;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NotificationCategoryController extends Controller
{
     /* Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 22-08-2022 || Service method Name :createNotificationCategory  || Description:  Add Notification Category  */

    // Add Notification Category Details

    public function addNotificationCategory(Request $request){
        
        $status = "ERROR";
        DB::beginTransaction();
        try{

            $arrData = $request->all();
            if (!empty($request->all())) {
                $validator = Validator::make($arrData, [
                    'notifyCatName'  => 'required|max:25|unique:notification,categoryName,'.',categoryId,deletedFlag,0',
                ], [
                    'notifyCatName.required'  => 'Notification Category Name is mandatory.',
                    'notifyCatName.max'       => 'Notification Category Name length exceded Max Limit.',
                    'notifyCatName.unique'    => 'The Category Name has already been taken..'
                
                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors();
                    $msg = array();
                    foreach ($errors->all() as $message) {
                        $msg[] = $message;
                    }
                    $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                } else {
                    $obj = new NotificationCategoryModel();
                    $obj->categoryName          = trim($arrData['notifyCatName']);
                    $obj->createdBy             = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;

                    if($obj->save()){
                        $status = "SUCCESS";
                        $statusCode = config('constant.SUCCESS_CODE');
                        $msg = "Notification Category added successfully";
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
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'NotificationCategoryController',
                'Method'     => 'addNotificationCategory',
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

    // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 22-08-2022 || Service method Name : viewNotificationCategory || Description:  View Notification Category Details list With Filter  
    public function viewNotificationCategory(Request $request){
        // return "slkdjflkjsdf";
        $msg = '';
        $status = "ERROR";
        try{
            $status = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
            $queryData = NotificationCategoryModel::selectRaw("categoryId,categoryName,parentId,createdOn")->where('parentId',0)->where('deletedflag',0);

            if(!empty($request->input("notifyCatName"))){
                $queryData->where('categoryName',trim($request->input("notifyCatName")));
            }
            $totalRecord = $queryData->count();

            $offset = (int)$request->input("offset") ? (int)$request->input("offset") : 0;
            $limit = (int)$request->input("limit") ? (int)$request->input("limit") : $totalRecord;

            $responseData = $queryData->offset($offset)->limit($limit)->orderBy('categoryName', 'ASC')->get();

            if($responseData->isEmpty()){
                $msg = 'No record found.';
                $success = true; 
            }else{
                $i = $offset;
                /* $response = array();
                foreach($responseData  as $res){
                    $res['encId']       = Crypt::encryptString($res->catId);
                    $res['categoryName']     = $res->catName;
                    $res['createdOn']   = $res->createdOn;
                    array_push($response,$res);
                }*/
                foreach ($responseData as $key => $value) {
                    $responseData[$key]->slNo = ++$i;
                    $responseData[$key]->encId = Crypt::encryptString($value->categoryId);
                }
                $success = true;  
            } 
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'NotificationCategoryController',
                'Method' => 'viewNotificationCategory',
                'Error'  => $t->getMessage()
            ]);
            $status = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg = config('constant.EXCEPTION_MESSAGE');               
        }
        return response()->json([
            "status"        => $status,
            "statusCode"    => $statusCode,
            "sucess"        => $success,
            "msg"           => $msg,
            "data"          => $responseData,
            "totalRecord"   => $totalRecord
        ],$statusCode);
    }

    // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 22-08-2022 || Service method Name : getNotificationCategoryData || Description:  Get Notification category details list  

    public function getNotificationCategoryData(Request $request) 
    {
       
        $msg = '';        
        $responseData = '';
        try{
            $status = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
            $queryData = NotificationCategoryModel::select('categoryId','categoryName','createdOn')
            ->where('deletedFlag', 0);
            if (!empty($request->input("id"))) {
                $id  = Crypt::decryptString($request->input("id"));
                $queryData->where('categoryId', $id);
            }
            $responseData = $queryData->get();
            
        
            if ($responseData->isEmpty()) {
                $msg = 'No record found.';
            } else {
                
                $response = array();
                foreach ($responseData as $res) {
                    $res['encId']               = Crypt::encryptString($res->categoryId);
                    array_push($response, $res);
                }
                $responseData = (count($response) > 1) ? $response : $response[0];
            }
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'NotificationCategoryController                                     ',
                'Method' => 'getNotificationCategoryData',
                'Error'  => $t->getMessage()
            ]);
            $status = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg = config('constant.EXCEPTION_MESSAGE');              
        }
        return response()->json([
            "status" => $status,
            "statusCode" => $statusCode,
            "msg" => $msg,
            "data" => $responseData
        ], $statusCode);
    }

     // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 22-08-2022 || Service method Name : updateNotificationCategory || Description:  Update Notification category Details list 
     public function updateNotificationCategory(Request $request)
     {
         
         $status = "ERROR";
         DB::beginTransaction();
         try {
             $arrData = $request->all();
             $id  = Crypt::decryptString($request->input("encId"));
             if (!empty($request->all())) {
                $validator = Validator::make($arrData, [
                    'notifyCatName'  => 'required|max:25|unique:notification,categoryName,'.$id.',categoryId,deletedFlag,0',
                ], [
                    'notifyCatName.required'  => 'Notification Category Name is mandatory.',
                    'notifyCatName.max'       => 'Notification Category Name length exceded Max Limit.',
                    'notifyCatName.unique'    => 'The Category Name has already been taken..'
                
                ]);
 
                 if ($validator->fails()) {
                     $errors = $validator->errors();
                     $msg = array();
                     foreach ($errors->all() as $message) {
                         $msg[] = $message;
                     }
                     $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                 } else { 
                     // $categoryName      = trim($arrData['categoryName']);
                     $dataArr['categoryName']       = trim($arrData['notifyCatName']);
                     $dataArr['updatedOn']          = Carbon::now('Asia/Kolkata');
                     $dataArr['updatedBy']          = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
                     $upObj = NotificationCategoryModel::where('categoryId',$id)->update($dataArr);
                     //print_r($upObj);exit;
                     if ($upObj) {
                         $status     = "SUCCESS";
                         $statusCode = config('constant.SUCCESS_CODE');
                         $msg        = "Notification Category Updated successfully";
                     } else {
                         $statusCode = config('constant.DB_EXCEPTION_CODE');
                         $msg        = "Something went wrong while storing the data.";
                     }
                 }
             } else {
                 $statusCode = config('constant.REQUEST_ERROR_CODE');
                 $msg        = "Something went wrong, Please try later.";
             }
             DB::commit(); 
         }
         catch (\Throwable $t) {
             Log::error("Error", [
                 'Controller' => 'NotificationCategoryController',
                 'Method'     => 'updateNotificationCategory',
                 'Error'      => $t->getMessage()
             ]);
             DB::rollback();
             $status     = "ERROR";
             $statusCode = config('constant.EXCEPTION_CODE');
             $msg        = config('constant.EXCEPTION_MESSAGE');               
         }
         return response()->json([
             "status" => $status,
             "statusCode" => $statusCode,
             "msg" => $msg
         ], $statusCode);
     }

     // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 22-08-2022 || Service method Name : deleteNotificationCategory || Description:  Delete Notification Category Details list  
     public function deleteNotificationCategory(Request $request)
     {
        DB::beginTransaction();
        try{
            $id = Crypt::decryptString($request->input("encId"));
        // return $id;
            $status = "ERROR";
            $obj = NotificationCategoryModel::find($id);
            $dataArr['deletedFlag'] = 1;
            $dataArr['updatedOn']   = Carbon::now('Asia/Kolkata');
            $dataArr['updatedBy']   = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
            
            $upObj = NotificationCategoryModel::where('catId',$id)->update($dataArr);
            if ($upObj) {
                $status = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $msg = "Notification category Details deleted successfully";
                $success = true;
            } else {
                $statusCode = 402;
                $msg = "Something went wrong while deleting the data.";
                $success = false;
            }
            DB::commit(); 
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'FeedbackCategoryController',
                'Method'     => 'deleteFeedbackCategory',
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
             "success" => $success
         ], $statusCode);
     }
}
