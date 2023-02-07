<?php
/**
    * Created By  : saubhagya ranjan patra
	* Created On  : 09-05-2022
	* Module Name : manage cluster
	* Description : block add, view,delete,edit,search actions.
**/ 
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\RedisController;
use App\Models\BlockModel;
use App\Models\ClusterModel; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ClusterController extends Controller
{
    
    /* Created By  :  Saubhgya Ranjan Patra ||  Created On  : 09-05-2022 || Service Method Name : getClusterById || Description:Get Cluster data for edit purpose  */
    public function getClusterById(Request $request)
    { 
        try{ 
            $msg = ''; $statusCode = config('constant.SUCCESS_CODE');
            $queryData = ClusterModel::select('clusterId','districtId','blockId','clusterName','clusterCode')->where('deletedflag',0);
            if (!empty($request->input("id"))) {
                $id  = Crypt::decryptString($request->input("id"));
                $queryData->where([['clusterId', $id]]);
            }
            $queryData = $queryData->with(['district','block']); 
            $responseData = $queryData->orderBy('clusterName', 'ASC')->get();

            if($responseData->isEmpty()){
                $msg = 'No record found.';
            }else{
                $response = array();
                foreach($responseData  as $res){
                    $res['encId']   = Crypt::encryptString($res->clusterId);
                    $res['districtName'] = $res->district->districtName;
                    $res['blockName']    = ($res->blockId>0)?$res->block->blockName:'';
                    array_push($response,$res);
                }
                $responseData = $response;
                $responseData = (count($response)>1)?$response:$response[0];          
            }
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'ClusterController',
                'Method'     => 'getCluster',
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

    /* Created By  :  Saubhgya Ranjan Patra ||  Created On  : 09-05-2022 || Service Method Name : addCluster || Description: Cluster creating Cluster */
    public function addCluster(Request $req)
    {
        
        $status = "ERROR";
        DB::beginTransaction();
        try{
        if(!empty(request()->all())) {
            $validator = Validator::make($req->all(), [ 
                'districtId'  => 'required',
                'blockId'     => 'required',
                'clusterName' => 'required|max:40|unique:clusters,clusterName,'.',clusterId,districtId,' .$req->input("districtId").',blockId,' .$req->input("blockId").',deletedFlag,0',
                'clusterCode' => 'required|digits_between:1,15|unique:clusters,clusterCode,' . ',clusterId,deletedFlag,0',
            ],[
                'districtId.required'  => 'District id is mandatory.',
                'blockId.required'     => 'Block id is mandatory.',
                'clusterName.required' => 'Cluster name is mandatory.',
                'clusterName.max'       => 'Cluster name length should not be greater than 40 characters.',
                'clusterName.unique'    => 'The combination of district, block, cluster name has already been taken.',
                'clusterCode.required' => 'Cluster code is mandatory.',
                'clusterCode.digits_between' => 'Cluster code length should not be greater than 5 digits.',
                'clusterCode.unique'         => 'Cluster code has already been taken.',
            ]);
    
            if($validator->fails()) {
                $errors = $validator->errors();
                $msg = array();
                foreach ($errors->all() as $message) {
                    $msg[] = $message;
                }
                $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
            }else{ 
                $blockDetails=BlockModel::selectRaw('districtCode,districtName,blockName,blockCode')->where('districtId', $req->input("districtId"))->where('blockId', $req->input("blockId"))->where('deletedFlag',0)->get();   
                $obj = new ClusterModel();
                $obj->districtName      = $blockDetails[0]->districtName;
                $obj->districtCode      = $blockDetails[0]->districtCode;
                $obj->blockName         = $blockDetails[0]->blockName;
                $obj->blockCode         = $blockDetails[0]->blockCode;
                $obj->districtId        = $req->input("districtId");
                $obj->blockId           = $req->input("blockId");
                $obj->clusterName       = $req->input("clusterName");
                $obj->clusterCode       = $req->input("clusterCode");
                $obj->createdBy         = (!empty($req->input("userId"))) ? Crypt::decryptString($req->input("userId")) : 0;
                if($obj->save()){
                    RedisController::setClusterListRedis();
                    $status = "SUCCESS";
                    $statusCode = config('constant.SUCCESS_CODE');
                    $msg = "Cluster added successfuly";
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
                'Controller' => 'ClusterController',
                'Method'     => 'addCluster',
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
    
    /* Created By  :  Saubhgya Ranjan Patra ||  Created On  : 09-05-2022 || Service Method Name : updateCluster || Description: Update Cluster data  */
    public function updateCluster(Request $req)
    {
        $status = "ERROR";
        DB::beginTransaction();
        try{
            if(!empty(request()->all())) {
        
                $id = Crypt::decryptString($req->input("encId")); 

                $validator = Validator::make($req->all(), [
                    'encId' => 'required',
                    'districtId' => 'required',
                    'blockId' => 'required',
                    'clusterName' => 'required|max:40|unique:clusters,clusterName,'.$id.',clusterId,districtId,' .$req->input("districtId").',blockId,' .$req->input("blockId").',deletedFlag,0',
                    'clusterCode' => 'required|digits_between:1,15|unique:clusters,clusterCode,' .$id. ',clusterId,deletedFlag,0',
                ],[
                'encId.required'       => 'Cluster id is mandatory.',
                'districtId.required'  => 'District id is mandatory.',
                'blockId.required'     => 'Block id is mandatory.',
                'clusterName.required' => 'Cluster name is mandatory.',
                'clusterName.max'       => 'Cluster name length should not be greater than 40 characters.',
                'clusterName.unique'    => 'The combination of district, block, cluster name has already been taken.',
                'clusterCode.required' => 'Cluster code is mandatory.',
                'clusterCode.digits_between' => 'Cluster code length should not be greater than 5 digits.',
                'clusterCode.unique'         => 'Cluster code has already been taken.',
                ]);
      
                if($validator->fails()) {
                    $errors = $validator->errors();
                    $msg = array();
                    foreach ($errors->all() as $message) {
                        $msg[] = $message;
                    }
                    $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                }else{   
                    $blockDetails=BlockModel::selectRaw('districtCode,districtName,blockName,blockCode')->where('districtId', $req->input("districtId"))->where('blockId', $req->input("blockId"))->where('deletedFlag',0)->get();  
                    $dataArr['districtName']   = $blockDetails[0]->districtName;
                    $dataArr['districtCode']   = $blockDetails[0]->districtCode;
                    $dataArr['blockName']      = $blockDetails[0]->blockName;
                    $dataArr['blockCode']      = $blockDetails[0]->blockCode;
                    $dataArr['districtId']     = trim($req->input("districtId"));
                    $dataArr['blockId']        = trim($req->input("blockId"));
                    $dataArr['clusterName']    = trim($req->input("clusterName"));
                    $dataArr['clusterCode']    = trim($req->input("clusterCode"));
                    $dataArr['updatedOn']      = Carbon::now('Asia/Kolkata');
                    $dataArr['updatedBy']      = (!empty($req->input("userId"))) ? Crypt::decryptString($req->input("userId")) : 0;

                    $upObj = ClusterModel::where('clusterId',$id)->update($dataArr);
                    if($upObj){
                        RedisController::setClusterListRedis();
                        $status = "SUCCESS";
                        $statusCode = config('constant.SUCCESS_CODE');
                        $msg = "Cluster updated successfuly";
                    }else{
                        $statusCode = config('constant.DB_EXCEPTION_CODE');
                        $msg = "Something went wrong while updating the data.";
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
                'Controller' => 'ClusterController',
                'Method'     => 'updateCluster',
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
    
    /* Created By  :  Saubhgya Ranjan Patra ||  Created On  : 09-05-2022 || Service Method Name : deleteCluster || Description: Delete Cluster  */
    public function deleteCluster(Request $request)
    {   
        $status = "ERROR";
        DB::beginTransaction();
        try{            
            $dataArr['deletedFlag']     = 1;
            $dataArr['updatedOn']       = Carbon::now('Asia/Kolkata');
            $id                         = Crypt::decryptString($request->input("encId"));
            $dataArr['updatedBy']       = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;

            $upObj = ClusterModel::where('clusterId',$id)->update($dataArr);
            if($upObj){
                RedisController::setClusterListRedis();
                $status     = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $msg        = "Cluster deleted successfuly";
                $success = true;
            }else{
                $statusCode = config('constant.DB_EXCEPTION_CODE');
                $msg = "Something went wrong while deleting the data.";
                $success = false;
            }
            DB::commit();
        }
        catch(\Throwable $t){
            Log::error("Error", [
                'Controller' => 'ClusterController',
                'Method'     => 'deleteCluster',
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
    
    /* Created By  :  Saubhgya Ranjan Patra ||  Created On  : 09-05-2022 || Service Method Name : viewCluster || Description: Cluster view eith filter search  */
    public function viewCluster(Request $req)
    {
        $msg = '';
        $responseData   = ''; 
        try{   
        $statusCode = config('constant.SUCCESS_CODE');
        $serviceType= $req->input("serviceType");
        $queryData = ClusterModel::select('clusterId','districtId','blockId','clusterName','clusterCode')->where('deletedFlag',0);

        if(!empty($req->input("clusterId"))){
            $queryData->where('clusterId',trim($req->input("clusterId")));
        }
        if(!empty($req->input("districtId"))){
            $queryData->where('districtId',trim($req->input("districtId")));
        }
        if(!empty($req->input("blockId"))){
            $queryData->where('blockId',trim($req->input("blockId")));
        }
        if(!empty($req->input("clusterName"))){
            $queryData->where('clusterName',trim($req->input("clusterName")));
        }
        if(!empty($req->input("clusterCode"))){
            $queryData->where('clusterCode',trim($req->input("clusterCode")));
        }
         
        $queryData = $queryData->with(['district','block']); 

        $totalRecord = $queryData->count();
        if($serviceType != "Download"){
            $offset         = (int)$req->input("offset") ? (int)$req->input("offset") : 0;
            $limit          = (int)$req->input("limit") ? (int)$req->input("limit") : $totalRecord;
            $queryData      = $queryData->offset($offset)->limit($limit);
        }
        //$responseData = $queryData->offset($offset)->limit($limit)->orderBy('clusterName', 'ASC')->get();
        $responseData   = $queryData->orderBy('clusterName', 'ASC')->get();
        if($serviceType == "Download"){  
            $userId = Crypt::decryptString($req->input("userId"));
           $downloadResponse = $this->downloadClusterList($responseData, $userId);

           if($downloadResponse['statusCode'] == 200){                    
               $responseData   = $downloadResponse['data'];  
               $status         = "SUCCESS";
               $statusCode     = config('constant.SUCCESS_CODE');
           } else {
               $responseData   = "";
               $msg = 'Could not create and download file.';
           }
       } 


        //$responseData = $queryData->get();
       else{
        if($responseData->isEmpty()){
            $msg = 'No record found.';
            
        }else{
           /*  $response = array();
            foreach($responseData  as $res){
                $res['encId']        = Crypt::encryptString($res->clusterId);
                $res['districtName'] = $res->district->districtName;
                $res['blockName']    = ($res->blockId>0)?$res->block->blockName:'';
                array_push($response,$res);
            } */
            $i = $offset;
            foreach ($responseData as $key => $value) {
                $responseData[$key]->slNo = ++$i;
                $responseData[$key]->encId = Crypt::encryptString($value->clusterId);
                $responseData[$key]->districtName =$value->district->districtName;
                $responseData[$key]->blockName =$value->block->blockName;
            }
            $status     = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
        }
    }
}
catch (\Throwable $t) {
    Log::error("Error", [
        'Controller' => 'ClusterController',
        'Method' => 'viewCluster',
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
            "data" => $responseData,
            //"sucess"        => $success,
            "totalRecord" => $totalRecord
        ],$statusCode);
    }
    /* Created By : Nitish Nanda || Created On : 19-08-2022 || Service method Name : downloadClusterList || Description: downloadClusterList data  */
    private function downloadClusterList($getCsvData, $userId)
    { 
        $status     = "ERROR";
        $statusCode = config('constant.EXCEPTION_CODE');
        $msg        = '';
        $responseData = '';  
        try { 
            $csvFileName = "Cluster_List_".$userId."_".time().".csv";
            $csvColumns = ["Sl. No","District", "Block Name","Cluster Name", "Cluster Code"];

            $csvDataArr = array();
            $slno = 1;
            foreach($getCsvData as $csvData){
                $csvDataArr[] = [
                    $slno,
                    !empty($csvData->district->districtName) ? $csvData->district->districtName : '--',
                    !empty($csvData->block->blockName) ? $csvData->block->blockName : '--',
                    !empty($csvData->clusterName) ? $csvData->clusterName : '--',
                    !empty($csvData->clusterCode) ? $csvData->clusterCode : '--',
                    
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
                'Controller' => 'ClusterController',
                'Method' => 'downloadClusterList',
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


