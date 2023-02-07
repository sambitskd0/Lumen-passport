<?php
/**
 * Created By  : Manoj Kumar Baliarsingh
 * Created On  : 16-05-2022
 * Module Name : Master Module
 * Description : FeedbackCategory Details Add, View, Update, Delete, Filter actions.
 **/


namespace App\Http\Controllers;

use App\Models\FeedbackCategoryModel;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FeedbackCategoryController extends Controller
{
     /* Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 16-05-2022 || Service method Name :createFeedback  || Description:  Add Feedback Category  */

    // Add FeedbackCategory Details

    public function addFeedbackCategory(Request $request){
        
        $status = "ERROR";
        DB::beginTransaction();
        try{

            $arrData = $request->all();
            if (!empty($request->all())) {
                $validator = Validator::make($arrData, [
                    'catName'  => 'required|max:25|unique:feedbackCategories,catName,'.',catId,deletedFlag,0',
                ], [
                    'catName.required'  => 'Feedback Category Name is mandatory.',
                    'catName.max'       => 'Feedback Category Name length exceded Max Limit.',
                    'catName.unique'    => 'The Category Name has already been taken..'
                
                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors();
                    $msg = array();
                    foreach ($errors->all() as $message) {
                        $msg[] = $message;
                    }
                    $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                } else {
                    $obj = new FeedbackCategoryModel();
                    $obj->catName = trim($arrData['catName']);
                    $obj->createdBy             = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;

                    if($obj->save()){
                        $status = "SUCCESS";
                        $statusCode = config('constant.SUCCESS_CODE');
                        $msg = "FeedbackCategory added successfully";
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
                'Controller' => 'FeedbackCategoryController',
                'Method'     => 'addFeedbackCategory',
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
    
    
    // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 16-05-2022 || Service method Name : getFeedbackData || Description:  Get Feedback category details list  

    public function getFeedbackCategory(Request $request) 
    {
        $msg = '';        
        $responseData = '';
        try{
            $status = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
            $queryData = FeedbackCategoryModel::select('catId','catName','createdOn')
            ->where([['deletedFlag', 0]]);
            if (!empty($request->input("id"))) {
                $id  = Crypt::decryptString($request->input("id"));
                $queryData->where([['catId', $id]]);
            }
            $responseData = $queryData->get();
            
        
            if ($responseData->isEmpty()) {
                $msg = 'No record found.';
            } else {
                $response = array();
                foreach ($responseData as $res) {
                    $res['encId']               = Crypt::encryptString($res->catId);
                    $res['catName']             = $res->catName;
                    $res['createdOn']           = $res->createdOn;
                    array_push($response, $res);
                }
                $responseData = (count($response) > 1) ? $response : $response[0];
            }
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'FeedbackCategoryController',
                'Method' => 'getFeedbackCategory',
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

    // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 16-05-2022 || Service method Name : updateFeedback || Description:  Update Feedback category Details list 
    public function updateFeedbackCategory(Request $request)
    {
        
        $status = "ERROR";
        DB::beginTransaction();
        try {
            $arrData = $request->all();
            $id  = Crypt::decryptString($request->input("encId"));
            if (!empty($request->all())) {
                $validator = Validator::make($arrData, [
                    'catName'  => 'required|max:25|unique:feedbackCategories,catName,'. $id .',catId,deletedFlag,0'
                ], [
                    'catName.required'  => 'Feedback Category Name is mandatory.',
                    'catName.max'       => 'Feedback Category Name length exceded Max Limit.',
                    'catName.unique'    => 'The Category Name has already been taken..'
                
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
                    $dataArr['catName']      = trim($arrData['catName']);
                    $dataArr['updatedOn']    = Carbon::now('Asia/Kolkata');
                    $dataArr['updatedBy']    = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
                    // $upObj = FeedbackCategoryModel::where('catId',$id)->update(['catName'=>$categoryName]);
                    $upObj = FeedbackCategoryModel::where('catId',$id)->update($dataArr);
                    //print_r($upObj);exit;
                    if ($upObj) {
                        $status     = "SUCCESS";
                        $statusCode = config('constant.SUCCESS_CODE');
                        $msg        = "FeedbackCategory Updated successfuly";
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
                'Controller' => 'FeedbackCategoryController',
                'Method'     => 'updateFeedbackCategory',
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

     // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 16-05-2022 || Service method Name : deleteFeedback || Description:  Delete FeedbackCategory Details list  
     public function deleteFeedbackCategory(Request $request)
     {
        DB::beginTransaction();
        try{
            $id = Crypt::decryptString($request->input("encId"));
        // return $id;
            $status = "ERROR";
            $obj = FeedbackCategoryModel::find($id);
            $dataArr['deletedFlag'] = 1;
            $dataArr['updatedOn']   = Carbon::now('Asia/Kolkata');
            $dataArr['updatedBy']   = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
            
            $upObj = FeedbackCategoryModel::where('catId',$id)->update($dataArr);
            if ($upObj) {
                $status = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $msg = "FeedbackCategory Details deleted successfully";
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

     // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 16-05-2022 || Service method Name : viewFeedbackCategory || Description:  View FeedbackCategory Detail list With Filter  
    public function viewFeedbackCategory(Request $request){
        // return "slkdjflkjsdf";
        $msg = '';
        $status = "ERROR";
        $responseData   = '';    
        try{
            $status = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
            $serviceType= $request->input("serviceType");
            $queryData = FeedbackCategoryModel::select('catId','catName','createdOn')->where('deletedflag',0);

            if(!empty($request->input("catName"))){
                $queryData->where('catName',trim($request->input("catName")));
            }
            $totalRecord = $queryData->count();

            if($serviceType != "Download"){
                $offset         = (int)$request->input("offset") ? (int)$request->input("offset") : 0;
                $limit          = (int)$request->input("limit") ? (int)$request->input("limit") : $totalRecord;
                $queryData      = $queryData->offset($offset)->limit($limit);
            }
            //$responseData = $queryData->offset($offset)->limit($limit)->orderBy('catName', 'ASC')->get();
            $responseData   = $queryData->orderBy('catName', 'ASC')->get();
            if($serviceType == "Download"){  
                $userId = Crypt::decryptString($request->input("userId"));
               $downloadResponse = $this->downloadFeedbackCatList($responseData, $userId);
   
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
            if($responseData->isEmpty()){
                $msg = 'No record found.';
             
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
                    $responseData[$key]->encId = Crypt::encryptString($value->catId);
                }
                $status     = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
            } 
        }
    }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'FeedbackCategoryController',
                'Method' => 'viewFeedbackCategory',
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
            "data" => $responseData,
            "totalRecord" => $totalRecord
        ],$statusCode);
    }

 /* Created By : Nitish Nanda || Created On : 24-08-2022 || Service method Name : downloadFeedbackCatList || Description: downloadFeedbackCatList data  */


    private function downloadFeedbackCatList($getCsvData, $userId)
    { 
        $status     = "ERROR";
        $statusCode = config('constant.EXCEPTION_CODE');
        $msg        = '';
        $responseData = '';  
        try { 
            $csvFileName = "FeedbackCat_List_".$userId."_".time().".csv";
            $csvColumns = ["Sl. No", "catName","Created On"];

            $csvDataArr = array();
            $slno = 1;
            foreach($getCsvData as $csvData){
                $csvDataArr[] = [
                    $slno,
                    !empty($csvData->catName) ? $csvData->catName : '--',
                    !empty($csvData->createdOn) ? $csvData->createdOn : '--',
                  
                    
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
                'Controller' => 'FeedbackCategoryController',
                'Method' => 'downloadFeedbackCatList',
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
}
