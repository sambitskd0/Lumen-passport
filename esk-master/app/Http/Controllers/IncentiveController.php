<?php
/**
 * Created By  : Manoj Kumar Baliarsingh
 * Created On  : 31-05-2022
 * Module Name : Master Module
 * Description : IncentiveMaster Details Add, View, Update, Delete, Filter actions.
 **/

namespace App\Http\Controllers;
use App\Models\IncentiveModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
class IncentiveController extends Controller
{
     /* Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 31-05-2022 || Service method Name :createIncentive  || Description:  Add Incentive Details  */

    public function addIncentiveData(Request $request){
        $status = "ERROR";
        $msg = '';
        DB::beginTransaction();
        try{
            $arrData = $request->all();
            // return $arrData;
            if (!empty($request->all())) {
                $validator = Validator::make($arrData, [
                    'incentiveName'             => 'required|max:40|unique:incentiveMasters,incentiveName,'.',incentiveId,incentiveCode,'.$request->input("incentiveCode").',deletedFlag,0',
                    // 'incentiveName'                => 'required|max:40',
                    'incentiveCode'             => 'required|max:15|unique:incentiveMasters,incentiveCode,'.',incentiveId,deletedFlag,0',
                    // 'incentiveCode'                 => 'required|numeric|digits_between:4,15',
                    'incentiveDescription'          => 'required|max:300',
                    'incentiveUnit'                 => 'required|numeric',
                    'disbursalMode'                 => 'required|numeric',
                
                ], [
                    'incentiveName.required'            => 'Incentive Name is mandatory.',
                    'incentiveCode.required'            => 'Incentive Code Name is mandatory.',
                    'incentiveDescription.required'     => 'Incentive Description Type is mandatory.',
                    
                    'incentiveUnit.required'            => 'Incentive Unit Type is mandatory.',
                    'disbursalMode.required'            => 'Disbursal Mode Type is mandatory.',

                    'incentiveName.max'                 => 'Incentive Name length exceded Max Limit.',
                    'incentiveCode.max'                 => 'Incentive Code length exceded Max Limit.',
                    'incentiveDescription.max'          => 'Incentive Description length exceded Max Limit.',
                    'incentiveCode.unique'              => 'Incentive Code has already been taken.',
                    'incentiveName.unique'              => 'Incentive Name has already been taken. based on Incentive Code',
                ]);

                if ($validator->fails()) {
                    
                    $errors = $validator->errors();
                    $msg = array();
                    foreach ($errors->all() as $message) {
                        $msg[] = $message;
                    }
                    $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                } else {
                    
                    $obj = new IncentiveModel();
                    
                    $obj->incentiveName         = trim($arrData['incentiveName']);
                    $obj->incentiveCode         = trim($arrData['incentiveCode']);
                    $obj->incentiveDescription  = trim($arrData['incentiveDescription']);
                    $obj->incentiveUnit         = $arrData['incentiveUnit'];
                    $obj->disbursalMode         = $arrData['disbursalMode'];
                    $obj->createdBy             = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
                    if($obj->save()){
                        $status = "SUCCESS";
                        $statusCode = config('constant.SUCCESS_CODE');
                        $msg = "IncentiveData added successfuly";
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
                'Controller' => 'IncentiveController',
                'Method'     => 'addIncentiveData',
                'Error'      => $t->getMessage()
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

     // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 31-05-2022 || Service method Name : deleteIncentive || Description:  Get Incentive details list  

     public function getIncentiveData($id = NULL) 
     {   
        $msg = '';        
        $responseData = '';
        try{
            $status = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
            $queryData = IncentiveModel::select('incentiveId','incentiveName','incentiveCode','incentiveDescription','incentiveUnit','disbursalMode','createdOn')
            ->where('deletedFlag', 0);
            if (!empty($id)) {
                $id = Crypt::decryptString($id);
                $queryData->where('incentiveId', $id);
            }
            $responseData = $queryData->get();
            
            if ($responseData->isEmpty()) {
                $msg = 'No record found.';
            } else {
                $response = array();
                foreach ($responseData as $res) {
                    $res['encId']                  = Crypt::encryptString($res->incentiveId);
                    $res['incentiveName']          = $res->incentiveName;
                    $res['incentiveCode']          = $res->incentiveCode;
                    $res['incentiveDescription']   = $res->incentiveDescription;
                    $res['incentiveUnit']          = $res->incentiveUnit;
                    $res['disbursalMode']          = $res->disbursalMode;
                    $res['createdOn']              = $res->createdOn;
                    array_push($response, $res);
                }
                $responseData = (count($response) > 1) ? $response : $response[0];
            }
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller'    => 'IncentiveController',
                'Method'        => 'getIncentiveData',
                'Error'         => $t->getMessage()
            ]);
            $status = "ERROR";
            $statusCode     = config('constant.EXCEPTION_CODE');
            $msg            = config('constant.EXCEPTION_MESSAGE');              
        }
         return response()->json([
             "status"       => $status,
             "statusCode"   => $statusCode,
             "msg"          => $msg,
             "data"         => $responseData
         ], $statusCode);
     }
 
     // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 31-05-2022 || Service method Name : updateIncentive || Description:  Update Incentive Details list 
    public function updateIncentiveData(Request $request)
    {
        $msg = '';
        $status = "ERROR";
        DB::beginTransaction();
        try {
            $arrData = $request->all();
            $id  = Crypt::decryptString($request->input("encId"));
            if (!empty($request->all())) {
                $validator = Validator::make($arrData, [
                    'incentiveName'             => 'required|max:40|unique:incentiveMasters,incentiveName,'.$id.',incentiveId,incentiveCode,'.$request->input("incentiveCode").',deletedFlag,0',
                // 'incentiveName'                => 'required|max:40',
                    'incentiveCode'             => 'required|max:15|unique:incentiveMasters,incentiveCode,'.$id.',incentiveId,deletedFlag,0',
                // 'incentiveCode'                 => 'required|numeric|digits_between:4,15',
                'incentiveDescription'          => 'required|max:300',
                'incentiveUnit'                 => 'required|numeric',
                'disbursalMode'                 => 'required|numeric',
                
            ], [
                'incentiveName.required'            => 'Incentive Name is mandatory.',
                'incentiveCode.required'            => 'Incentive Code Name is mandatory.',
                'incentiveDescription.required'     => 'Incentive Description Type is mandatory.',
                
                'incentiveUnit.required'            => 'Incentive Unit Type is mandatory.',
                'disbursalMode.required'            => 'Disbursal Mode Type is mandatory.',

                'incentiveName.max'                 => 'Incentive Name length exceded Max Limit.',
                'incentiveCode.max'                 => 'Incentive Code length exceded Max Limit.',
                'incentiveDescription.max'          => 'Incentive Description length exceded Max Limit.',
                'incentiveCode.unique'              => 'Incentive Code has already been taken.',
                'incentiveName.unique'              => 'Incentive Name has already been taken. based on Incentive Code',
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
                    $dataArr['incentiveName']               = trim($arrData['incentiveName']);
                    $dataArr['incentiveCode']               = trim($arrData['incentiveCode']);
                    $dataArr['incentiveDescription']        = trim($arrData['incentiveDescription']);
                    $dataArr['incentiveUnit']               = trim($arrData['incentiveUnit']);
                    $dataArr['disbursalMode']               = trim($arrData['disbursalMode']);
                    $dataArr['updatedOn']    = Carbon::now('Asia/Kolkata');
                    // $upObj = FeedbackCategoryModel::where('catId',$id)->update(['catName'=>$categoryName]);
                    $dataArr['updatedBy']               = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;

                    $upObj = IncentiveModel::where('incentiveId',$id)->update($dataArr);
                    //print_r($upObj);exit;
                    if ($upObj) {
                        $status     = "SUCCESS";
                        $statusCode = config('constant.SUCCESS_CODE');
                        $msg        = "IncentiveData Updated successfuly";
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
                'Controller' => 'IncentiveController',
                'Method'     => 'updateIncentiveData',
                'Error'      => $t->getMessage()
            ]);
            DB::rollback();
            $status     = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg        = config('constant.EXCEPTION_MESSAGE');               
        }
        return response()->json([
            "status"     => $status,
            "statusCode" => $statusCode,
            "msg"        => $msg
        ], $statusCode);
    }

    // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 31-05-2022 || Service method Name : deleteIncentiveData || Description:  Delete Incentive Detail list  
    public function deleteIncentiveData(Request $request)
    {
        $msg = '';
        $responseData = '';
        DB::beginTransaction();
        try{
            $id = Crypt::decryptString($request->input("encId"));
        // return $id;
            $status = "SUCCESS";
            $obj = IncentiveModel::find($id);

            $dataArr['deletedFlag'] = 1;
            $dataArr['updatedBy']   = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
            $dataArr['updatedOn']    = Carbon::now('Asia/Kolkata');

            $upObj = IncentiveModel::where('incentiveId',$id)->update($dataArr);
            if ($upObj) {
                $status     = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $msg        = "Incentive Details deleted successfully";
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
                'Controller' => 'IncentiveController',
                'Method' => 'deleteIncentiveData',
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
            'success'=>$success
        ], $statusCode);
    }

     // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 31-05-2022 || Service method Name : viewIncentive || Description:  View Incentive Detail list With Filter  
     public function viewIncentiveData(Request $request){
        $msg = '';
      $responseData = '';  
        try{
            $status = "ERROR";
            $statusCode = config('constant.SUCCESS_CODE');
            $serviceType= $request->input("serviceType");
            $queryData  = IncentiveModel::select('incentiveId','incentiveName','incentiveCode','incentiveDescription','incentiveUnit','disbursalMode','createdOn')->where('deletedflag',0);

            if(!empty($request->input("incentiveName"))){
                $queryData->where('incentiveId',trim($request->input("incentiveName")));
            }
            $queryData = $queryData->with('anexture'); 
            //$responseData = $queryData->get();
            $queryData = $queryData->where('deletedflag',0);
            $totalRecord = $queryData->count();

              if($serviceType != "Download"){
                $offset         = (int)$request->input("offset") ? (int)$request->input("offset") : 0;
                $limit          = (int)$request->input("limit") ? (int)$request->input("limit") : $totalRecord;
                $queryData      = $queryData->offset($offset)->limit($limit);
            }
     
            //$responseData = $queryData->offset($offset)->limit($limit)->orderBy('incentiveName', 'ASC')->get();
            $responseData   = $queryData->orderBy('incentiveName', 'ASC')->get();
            if($serviceType == "Download"){  
                $userId = Crypt::decryptString($request->input("userId"));
               $downloadResponse = $this->downloadIncentiveMasterList($responseData, $userId);
   
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
            else {
                $i = $offset;
                    foreach ($responseData as $key => $value) {
                        $responseData[$key]->slNo = ++$i;
                        $responseData[$key]->encId = Crypt::encryptString($value->incentiveId);
                        $responseData[$key]->incentiveUnitName = $value->anexture->anxtName;
                    }
                    $status     = "SUCCESS";
                    $statusCode = config('constant.SUCCESS_CODE');
                  
            }
            // else{
            //     $response = array();
            //     foreach($responseData  as $res){
            //         $res['encId']                   = Crypt::encryptString($res->incentiveId);
            //         $res['incentiveName']           = $res->incentiveName;
            //         $res['incentiveCode']           = $res->incentiveCode;
            //         $res['incentiveDescription']    = $res->incentiveDescription;
            //         $res['incentiveUnit']           = $res->incentiveUnit;
            //         $res['disbursalMode']           = $res->disbursalMode;
            //         $res['incentiveUnitName']       = $res->anexture->anxtName;
            //         $res['createdOn']               = $res->createdOn;
            //         array_push($response,$res);
            //     }
            // }
        }
    }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'IncentiveController',
                'Method' => 'viewIncentiveData',
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
        ],$statusCode);
    }

    /* Created By : Nitish Nanda || Created On : 19-08-2022 || Service method Name : downloadIncentiveMasterList || Description: downloadIncentiveMasterList data  */
    private function downloadIncentiveMasterList($getCsvData, $userId)
    { 
        $status     = "ERROR";
        $statusCode = config('constant.EXCEPTION_CODE');
        $msg        = '';
        $responseData = '';  
        try { 
            $csvFileName = "IncentiveMaster_List_".$userId."_".time().".csv";
            $csvColumns = ["Sl. No", "Incentive Name", "Code","
            Description","Unit","Mode of Disbursal
            "];

            $csvDataArr = array();
            $slno = 1;
            foreach($getCsvData as $csvData){
                $csvDataArr[] = [
                    $slno,
                    !empty($csvData->incentiveName) ? $csvData->incentiveName : '--',
                    !empty($csvData->incentiveCode) ? $csvData->incentiveCode : '--',
                    !empty($csvData->incentiveDescription) ? $csvData->incentiveDescription : '--',
                    !empty($csvData->anexture->anxtName) ? $csvData->anexture->anxtName : '--',
                    !empty($csvData->disbursalMode == 2) ? 'CASH' : (($csvData->disbursalMode == 1)? 'DBT': '--'), 
                    
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
                'Controller' => 'IncentiveController',
                'Method' => 'downloadIncentiveMasterList',
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
