<?php

/**
 * Created By  : Manoj Kumar Baliarsingh
 * Created On  : 12-05-2022
 * Module Name : 
 * Description : VillageWard Details Add, View, Update, Delete, Filter actions.
 **/

namespace App\Http\Controllers;

use App\Models\VillageModel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class VillageController extends Controller
{
    /* Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 12-05-2022 || Service method Name : getWardVillage || Description:  Get Village details list   */

    public function getVillage(Request $request)
    {
        
       
        $msg = '';        
        $responseData = '';
        try{
            $status = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
            $status = "SUCCESS";
            // $queryData = VillageModel::select('villageId', 'villageName', 'villageCode', 'districtId', 'blockId','panchayatId','villageType')
            // ->where('deletedFlag', 0);
            // if (!empty($request->input("id"))) {
            //     $id  = Crypt::decryptString($request->input("id"));
            //     $queryData->where([['villageId', $id]]);
            // }
            // $queryData = $queryData->with(['district','block','nagarnigam']);
            // $responseData = $queryData->get();
         
            // return $responseData[0]->block['blockName'];

            $queryData = DB::table('villages as v')
            ->leftJoin('districts as d', function ($join) {
                $join->on('d.districtId', '=', 'v.districtId')
                    ->where('d.deletedFlag', 0);
            })
            ->leftJoin('blocks as b', function ($join) {
                $join->on('b.blockId', '=', 'v.blockId')
                    ->where('b.deletedFlag', 0);
            })
            ->leftJoin('nagarnigams as ng', function ($join) {
                $join->on('ng.nagarId', '=', 'v.panchayatId')
                    ->where('ng.deletedFlag', 0);
            })
            ->where('v.deletedFlag', 0);

            if (!empty($request->input("id"))) {
                $id  = Crypt::decryptString($request->input("id"));
                $queryData->where('villageId', $id);
            }

        $queryData = $queryData->selectRaw("v.villageId,v.blockId,v.districtId,v.villageType,v.panchayatId,v.villageName,v.villageCode,v.villageType,d.districtName,b.blockName,ng.panchayatName");
        $responseData = $queryData->get();

            if ($responseData->isEmpty()) {
                $msg = 'No record found.';
            } else {
                $response = array();
                foreach ($responseData as $res) {
                    $resp['encId']           = Crypt::encryptString($res->villageId);                                
                    // $res['districtName']    = $res->district->districtName;
                    // $res['blockName']       = ($res->blockId)?$res->block->blockName:"";
                    // $res['panchayatName']   = $res->nagarnigam->panchayatName;
                    $resp['villageType']         = $res->villageType;
                    $resp['blockId']             = $res->blockId;
                    $resp['districtId']          = $res->districtId;
                    $resp['villageType']         = $res->villageType;
                    $resp['panchayatId']         = $res->panchayatId;
                    $resp['villageName']         = $res->villageName;
                    $resp['villageCode']       = $res->villageCode;

                    array_push($response, $resp);
                }
                $responseData = (count($response) > 1) ? $response : $response[0];
                // $responseData =  $response;
            }
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller'    => 'VillageController',
                'Method'        => 'getVillage',
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

    /* Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 12-05-2022 || Service method Name : createWardVillage || Description:  Add Village and Ward list  */

    public function addVillage(Request $request) 
    {
        //return $request;
        $status = "ERROR";
        DB::beginTransaction();
        try{
            $arrData = $request->all();
            //return $arrData;
            if (!empty($request->all())) {
                $validator = Validator::make($arrData, [
                    'villageType' => 'required',
                    'districtId'  => 'required',
                    'panchayatId' => 'required',
                    'blockId'     => 'required_if:villageType,==,2',
                    'villageName' => 'required_if:villageType,==,2|max:40',
                    'villageCode' => 'required_if:villageType,==,2|digits_between:1,15|unique:villages,villageCode,'.',villageId,villageName,'.trim($arrData['villageName']).',deletedFlag,0',
                    'wardName'    => 'required_if:villageType,==,1|max:40',
                    'wardCode'    => 'required_if:villageType,==,1|digits_between:1,15|unique:villages,villageCode,'.',villageId,villageName,'.trim($arrData['wardName']).',deletedFlag,0',
                ], [
                    'villageType.required'  => 'Village type is mandatory.',
                    'districtId.required'   => 'District id is mandatory.',
                    'blockId.required'      => 'Block id is mandatory.',
                    'panchayatId.required'  => 'Panchayat id is mandatory.',
                    'villageName.required'  => 'Village name is mandatory.',
                    'villageCode.required'  => 'Village code is mandatory.',
                    'wardName.required'     => 'Ward name is mandatory.',
                    'wardCode.required'     => 'Ward code is mandatory.',
                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors();
                    $msg = array();
                    foreach ($errors->all() as $message) {
                        $msg[] = $message;
                    }
                    $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                } else {
                    $obj = new VillageModel();
                    $obj->villageType           = trim($arrData['villageType']);
                    $obj->districtId            = trim($arrData['districtId']);
                    $obj->createdBy             = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
                    if ($arrData['villageType'] == 2) {
                        $obj->blockId           = trim($arrData['blockId']);
                        $obj->panchayatId       = trim($arrData['panchayatId']);
                        $obj->villageName       = trim($arrData['villageName']);
                        $obj->villageCode       = trim($arrData['villageCode']);
                    } else {
                        $obj->panchayatId       = trim($arrData['panchayatId']);
                        $obj->villageName       = trim($arrData['wardName']);
                        $obj->villageCode       = trim($arrData['wardCode']);
                    }

                    if ($obj->save()) {
                        $status = "SUCCESS";
                        $statusCode = config('constant.SUCCESS_CODE');
                        $msg = "Village/Ward added successfuly";
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
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'VillageController',
                'Method'     => 'addVillage',
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

    // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 12-05-2022 || Service method Name : updateWardVillage || Description:  Update Village Details list 
    public function updateVillage(Request $request)
    {
        $status = "ERROR";
        DB::beginTransaction();
        try {
            $status = "SUCCESS";
            $id = Crypt::decryptString($request->input("encId")); 
            $arrData = $request->all();
            //return $arrData;
            if (!empty($request->all())) {
                $validator = Validator::make($arrData, [
                    'encId'  => 'required',
                    'villageType'  => 'required',
                    'districtId'  => 'required',
                    'panchayatId' => 'required',
                    'blockId'     => 'required_if:villageType,==,2',
                    'villageName' => 'required_if:villageType,==,2|max:40',
                    'villageCode' => 'required_if:villageType,==,2|digits_between:1,15|unique:villages,villageCode,'.$id.',villageId,villageName,'.trim($arrData['villageName']).',deletedFlag,0',
                    'wardName'    => 'required_if:villageType,==,1|max:40',
                    'wardCode'    => 'required_if:villageType,==,1|digits_between:1,15|unique:villages,villageCode,'.$id.',villageId,villageName,'.trim($arrData['wardName']).',deletedFlag,0',
                ], [
                    'encId.required'  => 'Village Id is mandatory.', 
                    'villageType.required'  => 'Village type is mandatory.',
                    'districtId.required'   => 'District id is mandatory.',
                    'panchayatId.required'  => 'Panchayat id is mandatory.',
                    'blockId.required'      => 'Block id is mandatory.',
                    'villageName.required'  => 'Village Name is mandatory.',
                    'villageCode.required'  => 'Village code is mandatory.',
                    'wardName.required'     => 'Ward Name is mandatory.',
                    'wardCode.required'     => 'Ward code is mandatory.',
                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors();
                    $msg = array();
                    foreach ($errors->all() as $message) {
                        $msg[] = $message;
                    }
                    $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                } else {
                    $obj = VillageModel::find($id);   

                        $dataArr['villageType']       = trim($arrData['villageType']);
                        $dataArr['districtId']        = trim($arrData['districtId']);
                        $dataArr['updatedOn']         = Carbon::now('Asia/Kolkata');
                        $dataArr['updatedBy']         = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;

                    if ($arrData['villageType'] == 2) {
                        $dataArr['blockId']           = trim($arrData['blockId']);
                        $dataArr['panchayatId']       = trim($arrData['panchayatId']);
                        $dataArr['villageName']       = trim($arrData['villageName']);
                        $dataArr['villageCode']       = trim($arrData['villageCode']);
                    } else {
                        $dataArr['panchayatId']       = trim($arrData['panchayatId']);
                        $dataArr['villageName']       = trim($arrData['wardName']);
                        $dataArr['villageCode']       = trim($arrData['wardCode']);
                    }
                    
                    $upObj = VillageModel::where('villageId',$id)->update($dataArr);
                    //return  $upObj;
                    if ($upObj) {
                        $status = "SUCCESS";
                        $statusCode = config('constant.SUCCESS_CODE');
                        $msg = "Village/Ward Updated successfuly";
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
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'VillageController',
                'Method' => 'updateVillage',
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

    // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 12-05-2022 || Service method Name : deleteVillage || Description:  Delete Village Ward Details list  
    public function deleteVillage(Request $request)
    {
        $status = "ERROR";
        DB::beginTransaction();
        try{
            $status = "SUCCESS";
            $id  = Crypt::decryptString($request->input("encId"));

            $dataArr['deletedFlag']     = 1;
            $dataArr['updatedOn']       = Carbon::now('Asia/Kolkata');
            $dataArr['updatedBy']       = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;

            $upObj = VillageModel::where('villageId',$id)->update($dataArr);
            if($upObj){
                $status     = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $msg        = "Village deleted successfuly";
                $success    = true;
            } else {
                $statusCode = 402;
                $msg        = "Something went wrong while deleting the data.";
                $success    = false;
            }
            DB::commit(); 
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'VillageController',
                'Method' => 'deleteVillage',
                'Error'  => $t->getMessage()
            ]);
            DB::rollback();
            $status     = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg        = config('constant.EXCEPTION_MESSAGE');               
        }
        return response()->json([
            "success"    => $status,
            "statusCode" => $statusCode,
            "msg"        => $msg,
            "success" => $success
        ], $statusCode);
    }

    // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 12-05-2022 || Service method Name : viewWardVillage || Description:  View Village Ward Detail list With Filter  
    public function viewVillage(Request $req){
   
        $msg = '';
        $responseData = '';
        try{
            $status = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
            $serviceType= $req->input("serviceType");
           /*  $queryData = VillageModel::select('villageId','blockId','districtId','panchayatId','villageName','villageCode','villageType')->where('deletedflag',0);
            if(!empty($req->input("villageType"))){
                $queryData->where('villageType',$req->input("villageType"));
            }
            if(!empty($req->input("blockId"))){
                $queryData->where('blockId',$req->input("blockId"));
            }
            if(!empty($req->input("districtId"))){
                $queryData->where('districtId',$req->input("districtId"));
            }
            if(!empty($req->input("blockName"))){
                $queryData->where('blockName',trim($req->input("blockName")));
            }
            if(!empty($req->input("panchayatId"))){
                $queryData->where('panchayatId',trim($req->input("panchayatId")));
            }
            if(!empty($req->input("blockCode"))){
                $queryData->where('blockCode',trim($req->input("blockCode")));
            }
            if(!empty($req->input("villageName"))){
                $queryData->where('villageName',trim($req->input("villageName")));
            }
            if(!empty($req->input("villageCode"))){
                $queryData->where('villageCode',trim($req->input("villageCode")));
            }
            $queryData = $queryData->with(['district','block','nagarnigam']); 
            $queryData = $queryData->orderBy('villageName', 'ASC');
            $responseData = $queryData->get(); */

            $queryData = DB::table('villages as v')
                ->leftJoin('districts as d', function ($join) {
                    $join->on('d.districtId', '=', 'v.districtId')
                        ->where('d.deletedFlag', 0);
                })
                ->leftJoin('blocks as b', function ($join) {
                    $join->on('b.blockId', '=', 'v.blockId')
                        ->where('b.deletedFlag', 0);
                })
                ->leftJoin('nagarnigams as ng', function ($join) {
                    $join->on('ng.nagarId', '=', 'v.panchayatId')
                        ->where('ng.deletedFlag', 0);
                })
                ->where('v.deletedFlag', 0);

                

            $queryData = $queryData->selectRaw("v.villageId,v.blockId,v.districtId,v.panchayatId,v.villageName,v.villageCode,v.villageType,d.districtName,b.blockName,ng.panchayatName");

            if(!empty($req->input("villageType"))){
                $queryData->where('villageType',$req->input("villageType"));
            }
            if(!empty($req->input("districtId"))){
                $queryData->where('v.districtId',$req->input("districtId"));
            }
            if(!empty($req->input("blockId"))){
                $queryData->where('blockId',$req->input("blockId"));
            }
            if(!empty($req->input("blockName"))){
                $queryData->where('blockName',trim($req->input("blockName")));
            }
            if(!empty($req->input("panchayatId"))){
                $queryData->where('panchayatId',trim($req->input("panchayatId")));
            }
            if(!empty($req->input("blockCode"))){
                $queryData->where('blockCode',trim($req->input("blockCode")));
            }
            if(!empty($req->input("villageName"))){
                $queryData->where('villageName',trim($req->input("villageName")));
            }
            if(!empty($req->input("villageCode"))){
                $queryData->where('villageCode',trim($req->input("villageCode")));
            }

            $totalRecord = $queryData->count();

            
        if($serviceType != "Download"){
                $offset         = (int)$req->input("offset") ? (int)$req->input("offset") : 0;
                $limit          = (int)$req->input("limit") ? (int)$req->input("limit") : $totalRecord;
                $queryData      = $queryData->offset($offset)->limit($limit);
            }
            
           // $responseData = $queryData->offset($offset)->limit($limit)->orderBy('villageName', 'ASC')->get();
            $responseData   = $queryData->orderBy('villageName', 'ASC')->get();
            if($serviceType == "Download"){  
                $userId = Crypt::decryptString($req->input("userId"));
               $downloadResponse = $this->downloadWardVillageList($responseData, $userId);
   
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
              
            }
            //else{
            //     $response = array();
            //     foreach($responseData  as $res){
            //         $res['encId']           = Crypt::encryptString($res->villageId);
            //         $res['districtName']    = $res->districtName;
            //         $res['blockName']       = $res->blockName;
            //         array_push($response,$res);
            //     }
            //     // $responseData = (count($response)>0)?$response:$response[0];
            //     $responseData = $response;
            // }
            else{
                $i = $offset;
                foreach ($responseData as $key => $value) {
                    $responseData[$key]->slNo = ++$i;
                    $responseData[$key]->encId = Crypt::encryptString($value->villageId);
                }
                $status     = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
            }
        }
    }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'VillageController',
                'Method'     => 'viewVillage',
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
          
            "totalRecord"   => $totalRecord,
           
        ],$statusCode);
    }
    /* Created By : Nitish Nanda || Created On : 19-08-2022 || Service method Name : downloadWardVillageList || Description: downloadWardVillageList data  */
    private function downloadWardVillageList($getCsvData, $userId)
    { 
        $status     = "ERROR";
        $statusCode = config('constant.EXCEPTION_CODE');
        $msg        = '';
        $responseData = '';  
        try { 
            $csvFileName = "Village_List_".$userId."_".time().".csv";
            $csvColumns = ["Sl. No", "District","Block","Panchayat/Municipalty
            ", "Type","Ward / Village Name
            ","Ward / Village Code
            "];

            $csvDataArr = array();
            $slno = 1;
            foreach($getCsvData as $csvData){
                $csvDataArr[] = [
                    $slno,
                    !empty($csvData->districtName) ? $csvData->districtName : '--',
                    !empty($csvData->blockName) ? $csvData->blockName : '--',
                    !empty($csvData->panchayatName) ? $csvData->panchayatName : '--',
                    !empty($csvData->villageType == 2) ? 'Village' : (($csvData->villageType == 1)? 'Ward': '--'),
                    !empty($csvData->villageName) ? $csvData->villageName : '--',
                    !empty($csvData->villageCode) ? $csvData->villageCode : '--',
                    
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
                'Controller' => 'VillageController',
                'Method' => 'downloadVillageList',
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
