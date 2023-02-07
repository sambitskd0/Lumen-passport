<?php
/**
 * Created By  : Manoj Kumar Baliarsingh
 * Created On  : 23-05-2022
 * Module Name : Master Module
 * Description : AssetItem Details Add, View, Update, Delete, Filter actions.
 **/

namespace App\Http\Controllers;

use App\Models\AssetItemModel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AssetItemController extends Controller
{
     /* Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 23-05-2022 || Service method Name :createAssetItem  || Description:  Add AssetItem Details  */

    public function addAssetItem(Request $request){
        
       
        $status = "ERROR";
        DB::beginTransaction();
        try{
            $arrData = $request->all();
            if (!empty($request->all())) {
                $validator = Validator::make($arrData, [
                    'assetType'             => 'required',
                    'assetCatId'            => 'required',
                    'assetItemName'         => 'required|max:25|unique:assetItems,assetItemName,'.',assetItemId,assetType,'.$request->input("assetType").',assetCatId,'.$request->input("assetCatId").',deletedFlag,0',
                    'itemMovable'           => 'required',
                    'itemFixed'             => 'required',
                    'itemUnit'              => 'required',
                    'assetDescription'      => 'max:500',
                ], [
                    'assetType.required'        => 'AssetItem Type is mandatory.',
                    'assetCatId.required'       => 'Asset Category Id is mandatory.',
                    'assetItemName.required'    => 'AssetItemName Type is mandatory.',
                    'itemMovable.required'      => 'AssetItemMovable Type is mandatory.',
                    'itemFixed.required'        => 'AssetItemFixed Type is mandatory.',
                    'itemUnit.required'         => 'AssetItemUnit Type is mandatory.',
                    'assetItemName.max'         => 'Asset Item Name length exceded Max Limit.',
                    'assetDescription.max'      => 'Asset Description length exceded Max Limit.',
                    'assetItemName.unique'      => 'AssetItemName Already Exist.',
                    
                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors();
                    $msg = array();
                    foreach ($errors->all() as $message) {
                        $msg[] = $message;
                    }
                    $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                } else {
                    $obj = new AssetItemModel();
                    $obj->assetType         = trim($arrData['assetType']);
                    $obj->assetCatId        = trim($arrData['assetCatId']);
                    $obj->assetItemName     = trim($arrData['assetItemName']);
                    $obj->itemMovable       = trim($arrData['itemMovable']);
                    $obj->itemFixed         = trim($arrData['itemFixed']);
                    $obj->itemUnit          = trim($arrData['itemUnit']);
                    $obj->assetDescription  = trim($arrData['assetDescription']);
                    $obj->createdBy         = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
                    if($obj->save()){
                        $status     = "SUCCESS";
                        $statusCode = config('constant.SUCCESS_CODE');
                        $msg        = "AssetItem added successfuly";
                    }else {
                        $statusCode = config('constant.DB_EXCEPTION_CODE');
                        $msg        = "Something went wrong while storing the data.";
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
                'Controller' => 'AssetItemController',
                'Method'     => 'addAssetItem',
                'Error'      => $t->getMessage()
            ]);
            DB::rollback();
            $status     = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg        = config('constant.EXCEPTION_MESSAGE');     
        }
        return response()->json([
            "status"        => $status,
            "statusCode"    => $statusCode,
            "msg"           => $msg
        ], $statusCode);
    } 

    // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 23-05-2022 || Service method Name :  || Description:  Get Asset Item details list  

    public function getAssetItem(Request $request) 
    {
        $msg = '';
        try{
            $status = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
            $queryData = AssetItemModel::select('assetItemId','assetType','assetCatId','assetItemName','itemMovable','itemFixed','itemUnit','assetDescription','createdOn')
            ->where('deletedFlag', 0);
            if (!empty($request->input("id"))) {
                $id  = Crypt::decryptString($request->input("id"));
                $queryData->where([['assetItemId', $id]]);
            }
            $responseData = $queryData->get();
            
        
            if ($responseData->isEmpty()) {
                $msg = 'No record found.';
            } else {
                $response = array();
                foreach ($responseData as $res) {
                    $res['encId']                = Crypt::encryptString($res->assetItemId);
                    $res['assetType']            = $res->assetType;
                    $res['assetCatId']           = $res->assetCatId;
                    $res['assetItemName']        = $res->assetItemName;
                    $res['itemMovable']          = $res->itemMovable;
                    $res['itemFixed']            = $res->itemFixed;
                    $res['itemUnit']             = $res->itemUnit;
                    $res['assetDescription']     = $res->assetDescription;
                    $res['createdOn']            = $res->createdOn;
                    array_push($response, $res);
                }
                $responseData = (count($response) > 1) ? $response : $response[0];
            }
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller'    => 'AssetItemController',
                'Method'        => 'getAssetItem',
                'Error'         => $t->getMessage()
            ]);
            $status = "ERROR";
            $statusCode     = config('constant.EXCEPTION_CODE');
            $msg            = config('constant.EXCEPTION_MESSAGE');              
        }
        return response()->json([
            "status"        => $status,
            "statusCode"    => $statusCode,
            "msg"           => $msg,
            "data"          => $responseData
        ], $statusCode);
    }

    // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 23-05-2022 || Service method Name :  || Description:  Delete AssetItemDetails list 

    public function deleteAssetItem(Request $request)
    {
        $status = "ERROR";
        DB::beginTransaction();
        try{
            $status = "SUCCESS";
            $id = Crypt::decryptString($request->input("encId"));
            $obj = AssetItemModel::find($id);
            $dataArr['deletedFlag']     = 1;
            $dataArr['updatedOn']       = Carbon::now('Asia/Kolkata');
            $dataArr['updatedBy']       = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;

            $upObj = AssetItemModel::where('assetItemId',$id)->update($dataArr);
            if ($upObj) {
                $status = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $msg = "AssetCategory Details deleted successfully";
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
                'Controller'    => 'AssetItemController',
                'Method'        => 'deleteAssetItem',
                'Error'         => $t->getMessage()
            ]);
            DB::rollback();
            $status      = "ERROR";
            $statusCode  = config('constant.EXCEPTION_CODE');
            $msg         = config('constant.EXCEPTION_MESSAGE');               
        }
        return response()->json([
            "success"       => $status,
            "statusCode"    => $statusCode,
            "msg"           => $msg,
            "success" => $success
        ], $statusCode);
    }

     // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 23-05-2022 || Service method Name :  || Description:  Update Asset Item Details list 
     public function updateAssetItem(Request $request)
     {
        $status = "ERROR";
        DB::beginTransaction();
        try {
            $id  = Crypt::decryptString($request->input("encId"));
            $arrData = $request->all();          
            if (!empty($request->all())) {
            $validator = Validator::make($arrData, [
                'assetType'              => 'required',
                'assetCatId'             => 'required',
                'assetItemName'          => 'required|max:25|unique:assetItems,assetItemName,'.$id.',assetItemId,assetType,'.$request->input("assetType").',assetCatId,'.$request->input("assetCatId").',deletedFlag,0',
                'itemMovable'            => 'required',
                'itemFixed'              => 'required',
                'itemUnit'               => 'required',
                'assetDescription'       => 'required|max:500',
            ], [
                'assetType.required'        => 'AssetItem Type is mandatory.',
                'assetCatId.required'       => 'Asset Name is mandatory.',
                'assetItemName.required'    => 'AssetItemName Type is mandatory.',
                'itemMovable.required'      => 'AssetItemMovable Type is mandatory.',
                'itemFixed.required'        => 'AssetItemFixed Type is mandatory.',
                'itemUnit.required'         => 'AssetItemUnit Type is mandatory.',
                'assetItemName.max'         => 'Asset Item Name length exceded Max Limit.',
                'assetDescription.max'      => 'Asset Description length exceded Max Limit.',
                'assetItemName.unique'      => 'AssetItemName Already Exist.',
                
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
                    $dataArr['assetType']               = trim($arrData['assetType']);
                    $dataArr['assetCatId']              = trim($arrData['assetCatId']);
                    $dataArr['assetItemName']           = trim($arrData['assetItemName']);
                    $dataArr['itemMovable']             = trim($arrData['itemMovable']);
                    $dataArr['itemFixed']               = trim($arrData['itemFixed']);
                    $dataArr['itemUnit']                = trim($arrData['itemUnit']);
                    $dataArr['assetDescription']        = trim($arrData['assetDescription']);
                    $dataArr['assetDescription']        = trim($arrData['assetDescription']);
                    $dataArr['updatedOn']               = Carbon::now('Asia/Kolkata');
                    $dataArr['updatedBy']               = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
                
                    $upObj = AssetItemModel::where('assetItemId',$id)->update($dataArr);
                    if ($upObj) {
                        $status     = "SUCCESS";
                        $statusCode = config('constant.SUCCESS_CODE');
                        $msg        = "AssetItem Updated successfuly";
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
                'Controller' => 'AssetItemController',
                'Method' => 'updateAssetItem',
                'Error'  => $t->getMessage()
            ]);
            DB::rollback();
            $status = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg = config('constant.EXCEPTION_MESSAGE');               
        }
         return response()->json([
             "status"       => $status,
             "statusCode"   => $statusCode,
             "msg"          => $msg
         ], $statusCode);
     }
 
     // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 23-05-2022 || Service method Name : viewAssetItemData || Description:  View AssetItem Detail list With Filter  
    public function viewAssetItem(Request $request){
        $msg = '';
        $responseData = '';
      
        try{
        // return 1;
        $status = "ERROR";
            $statusCode = config('constant.SUCCESS_CODE');
            $serviceType= $request->input("serviceType");
            $queryData = AssetItemModel::select('assetItemId','assetCatId','assetType','assetItemName','itemMovable','itemFixed','itemUnit','assetDescription','createdOn')->where('deletedflag',0);

            if(!empty($request->input("assetType"))){
                $queryData->where('assetType',$request->input("assetType"));
            }

            if(!empty($request->input("assetName"))){
                $queryData->where('assetName',$request->input("assetName"));
            }
            $queryData = $queryData->with('anexture','assetcategories','incentiveunit'); 

            $totalRecord = $queryData->count();

            if($serviceType != "Download"){
                $offset         = (int)$request->input("offset") ? (int)$request->input("offset") : 0;
                $limit          = (int)$request->input("limit") ? (int)$request->input("limit") : $totalRecord;
                $queryData      = $queryData->offset($offset)->limit($limit);
            }
            $responseData = $queryData->get();
            if($serviceType == "Download"){  
                $userId = Crypt::decryptString($request->input("userId"));
               $downloadResponse = $this->downloadAssetItemList($responseData, $userId);
   
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
               /*  $response = array();
                foreach($responseData  as $res){
                    $res['encId']               = Crypt::encryptString($res->assetItemId);
                    $res['assetTypeName']       = $res->anexture->anxtName;
                    $res['assetName']           = $res->assetcategories->assetName;
                    $res['assetItemName']       = $res->assetItemName;
                    $res['itemMovable']         = $res->itemMovable;
                    $res['itemFixed']           = $res->itemFixed;
                    $res['itemUnitName']        = $res->incentiveunit->anxtName;
                    $res['itemUnit']            = $res->itemUnit;
                    $res['assetDescription']    = $res->assetDescription;
                    $res['createdOn']           = $res->createdOn;
                    array_push($response,$res);
                } */
                $i = $offset;
                foreach ($responseData as $key => $value) {
                    $responseData[$key]->slNo = ++$i;
                    $responseData[$key]->encId = Crypt::encryptString($value->assetItemId);
                    // $responseData[$key]->assetItemName =$value->assetcategories->assetItemId;
                }
                $status     = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
            }
        }
    }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'AssetItemController',
                'Method' => 'viewAssetItem',
                'Error'  => $t->getMessage()
            ]);
            $status     = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg        = config('constant.EXCEPTION_MESSAGE');               
        }
        // return  $response;
        return response()->json([
            "status"        => $status,
            "statusCode"    => $statusCode,
            "msg"           => $msg,
            "data"          => $responseData, 
            "totalRecord" => $totalRecord
        ],$statusCode);
    }
    /* Created By : Nitish Nanda || Created On : 19-08-2022 || Service method Name : downloadAssetItemList || Description: downloadAssetItemList data  */
    private function downloadAssetItemList($getCsvData, $userId)
    { 
        $status     = "ERROR";
        $statusCode = config('constant.EXCEPTION_CODE');
        $msg        = '';
        $responseData = '';  
        try { 
            $csvFileName = "AssetItem_List_".$userId."_".time().".csv";
            $csvColumns = ["Sl. No", "Asset Type", "Asset Name","Asset Item Name","Movable","Fixed","Description"];

            $csvDataArr = array();
            $slno = 1;
            foreach($getCsvData as $csvData){
                $csvDataArr[] = [
                    $slno,
                    !empty($csvData->anexture->anxtName) ? $csvData->anexture->anxtName : '--',
                    !empty($csvData->assetcategories->assetName) ? $csvData->assetcategories->assetName : '--',
                    !empty($csvData->assetcategories->assetName) ? $csvData->assetcategories->assetName : '--',
                    !empty($csvData->assetItemName) ? $csvData->assetItemName : '--',
                   !empty($csvData->itemMovable == 2) ? 'No' : (($csvData->itemMovable == 1)? 'Yes': '--'),
                    !empty($csvData->itemFixed == 2) ? 'No' : (($csvData->itemFixed == 1)? 'Yes': '--'),
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
                'Controller' => 'AssetItemController',
                'Method' => 'downloadAssetItemList',
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
