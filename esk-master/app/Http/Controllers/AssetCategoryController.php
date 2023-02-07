<?php

/**
 * Created By  : Manoj Kumar Baliarsingh
 * Created On  : 19-05-2022
 * Module Name : Master Module
 * Description : AssetCategory Details Add, View, Update, Delete, Filter actions.
 **/

namespace App\Http\Controllers;

use App\Models\AssetCategoryModel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AssetCategoryController extends Controller
{
     /* Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 19-05-2022 || Service method Name :createAsset  || Description:  Add AssetCategory Details  */

    public function addAssetCategory(Request $request){
        
        $status = "ERROR";
        DB::beginTransaction();
        try{
            $arrData = $request->all();
            if (!empty($request->all())) {
                $validator = Validator::make($arrData, [
                    'assetType'         => 'required',
                    'assetName'         => 'required|max:50|unique:assetCategories,assetName,'.',assetCatId,assetType,'.$request->input("assetType").',deletedFlag,0',
                    'assetDescription'  => 'max:500|unique:assetCategories,assetDescription,'.',assetCatId,deletedFlag,0',
                ], [
                    'assetType.required'        => 'AssetCategory Type is mandatory.',
                    'assetName.max'             => 'Asset Category Name length exceded Max Limit.',
                    'assetDescription.max'      => 'Asset Description length exceded Max Limit.',
                    'assetDescription.unique'   => 'Asset Description Name has already been taken..',
                    'assetName.unique'          => 'Asset Name has already been taken.. according to asset type',
                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors();
                    $msg = array();
                    foreach ($errors->all() as $message) {
                        $msg[] = $message;
                    }
                    $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                } else {
                    $obj = new AssetCategoryModel();
                    $obj->assetType             = trim($arrData['assetType']);
                    $obj->assetName             = trim($arrData['assetName']);
                    $obj->assetDescription      = trim($arrData['assetDescription']);
                    $obj->createdBy             = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
                    if($obj->save()){
                        $status = "SUCCESS";
                        $statusCode = config('constant.SUCCESS_CODE');
                        $msg        = "AssetCategory added successfuly";
                    }else {
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
                'Controller' => 'AssetCategoryController',
                'Method'     => 'addAssetCategory',
                'Error'      => $t->getMessage()
            ]);
            DB::rollback();
            $status = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg = config('constant.EXCEPTION_MESSAGE');     
        }
        return response()->json([
            "status"        => $status,
            "statusCode"    => $statusCode,
            "msg"           => $msg
        ], $statusCode);
    }  
     
    // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 19-05-2022 || Service method Name : getAssetData || Description:  Get Asset category details list  

    public function getAssetCategory(Request $request) 
    {
        $msg = '';        
        $responseData = '';

        try{
            $status = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
            $queryData = AssetCategoryModel::select('assetCatId','assetType','assetName','assetDescription','createdOn')
            ->where([['deletedFlag', 0]]);
            if (!empty($request->input("id"))) {
                $id  = Crypt::decryptString($request->input("id"));
                $queryData->where([['assetCatId', $id]]);
            }
            $responseData = $queryData->get();
            
        
            if ($responseData->isEmpty()) {
                $msg = 'No record found.';
            } else {
                $response = array();
                foreach ($responseData as $res) {
                    $res['encId']               = Crypt::encryptString($res->assetCatId);
                    $res['assetType']           = $res->assetType;
                    $res['assetName']           = $res->assetName;
                    $res['assetDescription']    = $res->assetDescription;
                    $res['createdOn']           = $res->createdOn;
                    array_push($response, $res);
                }
                $responseData = (count($response) > 1) ? $response : $response[0];
            }
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller'    => 'AssetCategoryController',
                'Method'        => 'getAssetCategory',
                'Error'         => $t->getMessage()
            ]);
            DB::rollback();
            $status = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg = config('constant.EXCEPTION_MESSAGE');               
        }
        return response()->json([
            "status"        => $status,
            "statusCode"    => $statusCode,
            "msg"           => $msg,
            "data"          => $responseData
        ], $statusCode);
    }

    // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 19-05-2022 || Service method Name : updateAssetData || Description:  Update Asset category Details list 
    public function updateAssetCategory(Request $request)
    {
        // return $request->input("encId");
        DB::beginTransaction();
        try {
            $id  = Crypt::decryptString($request->input("encId"));
            $status = "ERROR";
            $arrData = $request->all();
            if (!empty($request->all())) {
                $validator = Validator::make($arrData, [
                    'assetType'         => 'required',
                    'assetName'         => 'required|max:50||unique:assetCategories,assetName,'.$id.',assetCatId,assetType,'.$request->input("assetType").',deletedFlag,0',
                    'assetDescription'  => 'required|max:500|unique:assetCategories,assetDescription,'.$id.',assetCatId,deletedFlag,0',
                ], [
                    'assetType.required'    => 'AssetCategory Type is mandatory.',
                    'assetName.max'         => 'Asset Category Name length exceded Max Limit.',
                    'assetDescription.max'  => 'Asset Description length exceded Max Limit.',
                    'assetDescription.unique'   => 'Asset Description Name has already been taken..',
                    'assetName.unique'          => 'Asset Name has already been taken.. according to asset type',
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
                    $dataArr['assetType']           = trim($arrData['assetType']);
                    $dataArr['assetName']           = trim($arrData['assetName']);
                    $dataArr['assetDescription']    = trim($arrData['assetDescription']);
                    $dataArr['updatedOn']           = Carbon::now('Asia/Kolkata');
                    $dataArr['updatedBy']           = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
                
                    $upObj = AssetCategoryModel::where('assetCatId',$id)->update($dataArr);
                    if ($upObj) {
                        $status     = "SUCCESS";
                        $statusCode = config('constant.SUCCESS_CODE');
                        $msg        = "AssetCategory Updated successfuly";
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
                'Controller' => 'AssetCategoryController',
                'Method' => 'updateAssetCategory',
                'Error'  => $t->getMessage()
            ]);
            DB::rollback();
            $status = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg = config('constant.EXCEPTION_MESSAGE');               
        }
        return response()->json([
            "status"     => $status,
            "statusCode" => $statusCode,
            "msg"        => $msg
        ], $statusCode);
    }

     // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 19-05-2022 || Service method Name : deleteAssetData || Description:  Delete AssetCategory Details list  
     public function deleteAssetCategory(Request $request)
     {
        DB::beginTransaction();
        try{
            $id = Crypt::decryptString($request->input("encId"));
        // return $id;
            $status = "ERROR";
            $obj = AssetCategoryModel::find($id);
            $dataArr['deletedFlag'] = 1;
            $dataArr['updatedOn']    = Carbon::now('Asia/Kolkata');
            $dataArr['updatedBy']   = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;

            $upObj = AssetCategoryModel::where('assetCatId',$id)->update($dataArr);
            if ($upObj) {
                $status     = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $msg        = "AssetCategory Details deleted successfully";
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
                'Controller' => 'AssetCategoryController',
                'Method'     => 'deleteAssetCategory',
                'Error'      => $t->getMessage()
            ]);
            DB::rollback();
            $status     = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg        = config('constant.EXCEPTION_MESSAGE');               
        }
         return response()->json([
             "success"      => $status,
             "statusCode"   => $statusCode,
             "msg"          => $msg,
             "success" => $success
         ], $statusCode);
     }

     // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 19-05-2022 || Service method Name : viewAssetData || Description:  View AssetCategory Detail list With Filter  
    public function viewAssetCategory(Request $request){
        $msg = '';
        $responseData = '';
        $status = "ERROR";
        // return $request->input("assetType");
        try{
            $status = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
            $serviceType= $request->input("serviceType");
            $queryData = AssetCategoryModel::select('assetCatId','assetType','assetName','assetDescription','createdOn')->where('deletedflag',0);

            if(!empty($request->input("assetType"))){
                $queryData->where('assetType',$request->input("assetType"));
                // return $queryData;
            }
            $queryData = $queryData->with('anexture'); 
            $totalRecord = $queryData->count();

            if($serviceType != "Download"){
                $offset         = (int)$request->input("offset") ? (int)$request->input("offset") : 0;
                $limit          = (int)$request->input("limit") ? (int)$request->input("limit") : $totalRecord;
                $queryData      = $queryData->offset($offset)->limit($limit);
            }
            //$responseData = $queryData->offset($offset)->limit($limit)->orderBy('assetName', 'ASC')->get();
            $responseData   = $queryData->orderBy('assetName', 'ASC')->get();
            if($serviceType == "Download"){  
                $userId = Crypt::decryptString($request->input("userId"));
               $downloadResponse = $this->downloadAssetCatList($responseData, $userId);
   
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
                    $res['encId']                   = Crypt::encryptString($res->assetCatId);
                    $res['assetTypeName']           = $res->anexture->anxtName;      
                    $res['createdOn']               = $res->createdOn;
                    array_push($response,$res); */
                foreach ($responseData as $key => $value) {
                    $responseData[$key]->slNo = ++$i;
                    $responseData[$key]->encId = Crypt::encryptString($value->assetCatId);
                }
                $status     = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
            }
        }
    }
        
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'AssetCategoryController',
                'Method'     => 'viewAssetCategory',
                'Error'      => $t->getMessage()
            ]);
            $status     = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg        = config('constant.EXCEPTION_MESSAGE');               
        }
        return response()->json([
            "status"        => $status,
            "statusCode"    => $statusCode,
            "msg"           => $msg,
            "data"          => $responseData,
            "totalRecord" => $totalRecord
        ],$statusCode);
    }

    // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 19-05-2022 || Service method Name : getAssetData || Description:  Get Asset category details list  

    public function getAssetNameByType($id = NULL) 
    {
        $msg = '';        
        $responseData = '';

        try{
            $status = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
            $queryData = AssetCategoryModel::select('assetCatId','assetType','assetName','assetDescription','createdOn')
            ->where([['deletedFlag', 0]]);
            if (!empty($id)) {
                $id = Crypt::decryptString($id);
                $queryData->where([['assetCatId', $id]]);
            }
            $responseData = $queryData->get();
            
        
            if ($responseData->isEmpty()) {
                $msg = 'No record found.';
            } else {
                $response = array();
                foreach ($responseData as $res) {
                    $res['encId']               = Crypt::encryptString($res->assetCatId);
                    $res['assetType']           = $res->assetType;
                    $res['assetName']           = $res->assetName;
                    $res['assetDescription']    = $res->assetDescription;
                    $res['createdOn']           = $res->createdOn;
                    array_push($response, $res);
                }
                $responseData = (count($response) > 1) ? $response : $response[0];
            }
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller'    => 'AssetCategoryController',
                'Method'        => 'getAssetCategory',
                'Error'         => $t->getMessage()
            ]);
            DB::rollback();
            $status = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg = config('constant.EXCEPTION_MESSAGE');               
        }
        return response()->json([
            "status"        => $status,
            "statusCode"    => $statusCode,
            "msg"           => $msg,
            "data"          => $responseData
        ], $statusCode);
    }
     /* Created By : Nitish Nanda || Created On : 19-08-2022 || Service method Name : downloadAssetCatList || Description: downloadAssetCatList data  */
     private function downloadAssetCatList($getCsvData, $userId)
     { 
         $status     = "ERROR";
         $statusCode = config('constant.EXCEPTION_CODE');
         $msg        = '';
         $responseData = '';  
         try { 
             $csvFileName = "Assetcat_List_".$userId."_".time().".csv";
             $csvColumns = ["Sl. No", "Asset Type
             ", "Asset Name
             ","Description
             "];
 
             $csvDataArr = array();
             $slno = 1;
             foreach($getCsvData as $csvData){
                 $csvDataArr[] = [
                     $slno,
                     !empty($csvData->assetType) ? $csvData->assetType : '--',
                     !empty($csvData->assetName) ? $csvData->assetName : '--',
                     !empty($csvData->assetDescription) ? $csvData->assetDescription : '--',
                     
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
                 'Controller' => 'AssetCategoryController',
                 'Method' => 'downloadAssetCatList',
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
