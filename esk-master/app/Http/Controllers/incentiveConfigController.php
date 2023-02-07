<?php

/**
 * Created By  : Manoj Kumar Baliarsingh
 * Created On  : 04-06-2022
 * Module Name : Incentive Module
 * Description : Incentive Config Master Details Add, View, Update, Delete, Filter actions.
 **/

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\IncentiveConfigModel;
use App\Models\IncentiveClassTaggedModel;
use App\Models\IncentiveModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class incentiveConfigController extends Controller
{
    /* Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 04-06-2022 || Service method Name :createIncentiveConfig  || Description:  Add Incentive Config Details  */
    public function addIncentiveConfigData(Request $request)
    {
       
        $status = "ERROR";
        DB::beginTransaction();
        try{
            $arrData = $request->all();
            // return $arrData;
            if (!empty($request->all())) {
                $validator = Validator::make($arrData, [
                    'incentiveId'                => 'required|numeric|unique:incentiveConfigMasters,incentiveId,'.',incentiveConfigId,deletedFlag,0',
                    'gender'                     => 'required|numeric',
                    'caste'                      => 'required|numeric',
                    'ageMax'                     => 'required|numeric',
                    'ageMin'                     => 'required|numeric',
                    'belongsToBPL'               => 'numeric|numeric',
                    'disabilityType'             => 'numeric',
                ], [
                    'incentiveId.required'              => 'Incentive Id is mandatory.',
                    'incentiveId.unique'              => 'Incentive Name is already Taken.',
                    // 'class.required'                    => 'Class Status is mandatory.',
                    'gender.required'                   => 'Gender Status is mandatory.',
                    'caste.required'                    => 'Caste Status is mandatory.',
                    'ageMax.required'                   => 'Maximum Age  Status is mandatory.',
                    'ageMin.required'                   => 'Minimum Age Status Mandatory',
                    'belongsToBPL.required'             => 'BPL Status Is Mandatory.',
                    'disabilityType.required'             => 'Disability Type Is Mandatory.',

                ]);

                if ($validator->fails()) {

                    $errors = $validator->errors();
                    $msg = array();
                    foreach ($errors->all() as $message) {
                        $msg[] = $message;
                    }
                    $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                } else {

                    // $frontEndClassTypearr=$request->input('classArr');
                    // $incentiveId=$request->input('incentiveId');
                    // return $incentiveId;

                    // $classArrType= IncentiveClassTaggedModel::selectRaw('group_concat(incClassTaggedId) as allsClassTaggedTypeId')
                    // ->where('deletedflag', 0)
                    // ->groupBy('incentiveConfigId')->get()->toArray();
                    // return  $classArrType;
                    // $matchsCount = 0;

                    // foreach ($classArrType  as $res) {
                    //     $res['allsClassTaggedTypeId'] = array_map('intval', explode(',', $res['allsClassTaggedTypeId']));
                        
                    //     if ($res['allsClassTaggedTypeId'] == $frontEndClassTypearr){
                    //         $matchsCount++;
                    //     }   
                    // }
                    // if($matchsCount!=0){
                    //     $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                    //     $msg = "The Combination of Class Tagging has already been Added";
                    // }
                    // else{
                    $objconfig = new IncentiveConfigModel();
                    $objconfig->incentiveId           = $arrData['incentiveId'];
                    $objconfig->gender                = $arrData['gender'];
                    $objconfig->caste                 = $arrData['caste'];
                    $objconfig->ageMax                = $arrData['ageMax'];
                    $objconfig->ageMin                = $arrData['ageMin'];
                    $objconfig->belongsToBPL          = $arrData['belongsToBPL'];
                    $objconfig->disabilityType        = $arrData['disabilityType'];
                    $objconfig->createdBy             = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;

                    if ($objconfig->save()) {
                        $lastInsertId = $objconfig->incentiveConfigId;

                        $classlTagdata = array();
                        $objclassTagged = new IncentiveClassTaggedModel();

                        foreach ($request->input('classArr')  as $res) {
                            $temp['incentiveConfigId']  = $lastInsertId;
                            $temp['incClassTaggedId']   =   $res;
                            $$temp['createdBy']         = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;

                            array_push($classlTagdata, $temp);
                        }
                        if (IncentiveClassTaggedModel::insert($classlTagdata)) {
                            $status = "SUCCESS";
                            $statusCode = 200;
                            $msg = "Incentive Config Data added successfully";
                        } else {
                            $statusCode = config('constant.DB_EXCEPTION_CODE');
                            $msg = "Something went wrong while storing the data.";
                        }
                    } else {
                        $statusCode = config('constant.DB_EXCEPTION_CODE');
                        $msg = "Something went wrong while storing the data.";
                    }
                // }
            }
            } else {
                $statusCode = config('constant.REQUEST_ERROR_CODE');
                $msg = "Something went wrong, Please try later.";
            }
            DB::commit();
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'incentiveConfigController',
                'Method'     => 'addIncentiveConfigData',
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

    // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 04-06-2022 || Service method Name : deleteIncentiveConfig || Description:  Get Incentive Config Details list  

    public function getIncentiveConfigData($id = NULL)
    {
        $msg = '';        
        $responseData = '';
        try{
            $status = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
            $queryData = IncentiveConfigModel::select('incentiveConfigId', 'incentiveId', 'gender', 'caste', 'ageMax', 'ageMin', 'belongsToBPL', 'disabilityType', 'createdOn')
                ->where('deletedFlag', 0);

            if (!empty($id)) {
                $id = Crypt::decryptString($id);
                $queryData->where('incentiveConfigId', $id);
            }
            $queryData = $queryData->with('anexturegender', 'anexturecaste', 'incentivemaster', 'anexturedisability', 'incconfigclasstagged');
            $responseData = $queryData->get();
            // return $responseData;
            if ($responseData->isEmpty()) {
                $msg = 'No record found.';
            } else {
                $response = array();
                foreach ($responseData as $res) {
                    $res['encId']                  = Crypt::encryptString($res->incentiveConfigId);
                    $res['incentiveId']            = $res->incentiveId;
                    $res['incentiveName']          = $res->incentivemaster->incentiveName;
                    $res['gender']                 = $res->gender;
                    $res['genderName']             = $res->anexturegender->anxtName;
                    $res['caste']                  = $res->caste;
                    $res['castName']               = $res->anexturecaste->anxtName;
                    $res['ageMax']                 = $res->ageMax;
                    $res['ageMin']                 = $res->ageMin;
                    $res['belongsToBPL']           = $res->belongsToBPL;
                    $res['disabilityName']         = $res->disabilityType;
                    $res['disabilityName']         = $res->anexturedisability->anxtName;
                    $res['createdOn']              = $res->createdOn;
                    array_push($response, $res);
                }
                $responseData = (count($response) > 1) ? $response : $response[0];
            }
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'incentiveConfigController',
                'Method'     => 'getIncentiveConfigData',
                'Error'      => $t->getMessage()
            ]);
            $status = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg = config('constant.EXCEPTION_MESSAGE');              
        }
        return response()->json([
            "status"     => $status,
            "statusCode" => $statusCode,
            "msg"        => $msg,
            "data"       => $responseData
        ], $statusCode);
    }

    // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 04-06-2022 || Service method Name : updateIncentiveConfig || Description:  Update Incentive Config Details list 
    public function updateIncentiveConfigData(Request $request)
    {
        $msg = '';
        $status = "ERROR";
        DB::beginTransaction();
        try {
            $arrData = $request->all();
            $id  = Crypt::decryptString($request->input("encId"));
            if (!empty($request->all())) {
                $validator = Validator::make($arrData, [
                    'incentiveId'                => 'required|numeric|unique:incentiveConfigMasters,incentiveId,'.$id.',incentiveConfigId,deletedFlag,0',
                    // 'class'                      => 'required',
                    'gender'                     => 'required|numeric',
                    'caste'                      => 'required|numeric',
                    'ageMax'                     => 'required|numeric',
                    'ageMin'                     => 'required|numeric',
                    'belongsToBPL'               => 'required|numeric',
                    'disabilityType'             => 'required|numeric',
                ], [
                    'incentiveId.required'              => 'Incentive Id is mandatory.',
                    // 'class.required'                    => 'Class Status is mandatory.',
                    'gender.required'                   => 'Gender Status is mandatory.',
                    'caste.required'                    => 'Caste Status is mandatory.',
                    'ageMax.required'                   => 'Maximum Age  Status is mandatory.',
                    'ageMin.required'                   => 'Minimum Age Status Mandatory',
                    'belongsToBPL.required'             => 'BPL Status Is Mandatory.',
                    'disabilityType.required'           => 'Disability Type Is Mandatory.',

                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors();
                    $msg = array();
                    foreach ($errors->all() as $message) {
                        $msg[] = $message;
                    }
                    $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                } else {
                    $dataArr['incentiveId']                 = trim($arrData['incentiveId']);
                    // $dataArr['class']                       = trim($arrData['class']);
                    $dataArr['gender']                      = trim($arrData['gender']);
                    $dataArr['caste']                       = trim($arrData['caste']);
                    $dataArr['ageMax']                      = trim($arrData['ageMax']);
                    $dataArr['ageMin']                      = trim($arrData['ageMin']);
                    $dataArr['belongsToBPL']                = trim($arrData['belongsToBPL']);
                    $dataArr['disabilityType']              = trim($arrData['disabilityType']);
                    $dataArr['updatedOn']                   = date('Y-m-d h:i:s');
                    $dataArr['updatedBy']               = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;

                    $upObj = IncentiveConfigModel::where('incentiveConfigId', $id)->update($dataArr);

                    //print_r($upObj);exit;
                    if ($upObj) {
                        $dataArrr['deletedFlag'] = 1;
                        $obj = IncentiveClassTaggedModel::where('incentiveConfigId', $id)->delete();
                        if ($obj) {
                            $inccentiveConfigTagdata = array();
                            foreach ($request->input('classArr')  as $res) {
                                $temp['incentiveConfigId'] = $id;
                                $temp['incClassTaggedId'] =   $res;
                                $temp['updatedBy']               = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
                                $temp['updatedOn']   = date('Y-m-d h:i:s');
                                array_push($inccentiveConfigTagdata, $temp);
                            }
                            if (IncentiveClassTaggedModel::insert($inccentiveConfigTagdata)) {
                                $status = "SUCCESS";
                                $statusCode = config('constant.SUCCESS_CODE');
                                $msg = "IncentiveConfigData Updated successfully";
                            } else {
                                $statusCode = config('constant.DB_EXCEPTION_CODE');
                                $msg = "Something went wrong while storing the data.";
                            }
                        } else {
                            $statusCode = config('constant.DB_EXCEPTION_CODE');
                            $msg = "Something went wrong while storing the data.";
                        }
                    } else {
                        $statusCode = config('constant.DB_EXCEPTION_CODE');
                        $msg = "Something went wrong while updating the data.";
                    }
                }
            }
            else{
                $statusCode = config('constant.REQUEST_ERROR_CODE');
                $msg = "Something went wrong, Please try later.";
            }
            DB::commit(); 
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'incentiveConfigController',
                'Method' => 'updateIncentiveConfigData',
                'Error'  => $t->getMessage()
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

    // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 04-06-2022 || Service method Name : deleteIncentiveConfigData || Description:  Delete Incentive Config Detail list  
    public function deleteIncentiveConfigData(Request $request)
    {
        $msg = '';
        DB::beginTransaction();
        try{
            $id = Crypt::decryptString($request->input("encId"));
            // return $id;
            $status = "SUCCESS";
            // $obj = IncentiveConfigModel::find($id);
            $dataArr['deletedFlag'] = 1;
            $dataArr['updatedOn']   = date("Y-m-d h:i:s");
            $dataArr['updatedBy']   = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;

            $upObj = IncentiveConfigModel::where('incentiveConfigId', $id)->update($dataArr);

            if ($upObj) {
                $upClassObj = IncentiveClassTaggedModel::where('incentiveConfigId', $id)->update($dataArr);
                $status     = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $msg        = "Incentive Config Details deleted successfully";
                $success = true;
            } else {
                $statusCode = 402;
                $msg        = "Something went wrong while deleting the data.";
                $success = false;
            }
            DB::commit(); 
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller'    => 'incentiveConfigController',
                'Method'        => 'deleteIncentiveConfigData',
                'Error'         => $t->getMessage()
            ]);
            DB::rollback();
            $status     = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg        = config('constant.EXCEPTION_MESSAGE');               
        }
        return response()->json([
            "success"       => $status,
            "statusCode"    => $statusCode,
            "msg"           => $msg,
            'success'=>$success
        ], $statusCode);
    }

    // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 04-06-2022 || Service method Name : viewIncentiveConfig || Description:  View Incentive Config Detail list With Filter  
    public function viewIncentiveConfigData(Request $request)
    {
        $msg = '';
        $responseData = ''; 
        try{
            $status = "ERROR";
            $statusCode = config('constant.SUCCESS_CODE');
            $serviceType= $request->input("serviceType");
            $queryData = DB::table('incConfigClassTagged as ICMT')
                ->leftJoin('incentiveConfigMasters as ICM', function ($join) {
                    $join->on('ICM.incentiveConfigId', '=', 'ICMT.incentiveConfigId')
                        ->where('ICMT.deletedFlag', 0);
                })
                ->leftJoin('incentiveMasters as IM', function ($join) {
                    $join->on('IM.incentiveId', '=', 'ICM.incentiveId')
                        ->where('ICM.deletedFlag', 0);
                })
                ->leftJoin('annexture as ANNXC', function ($join) {
                    $join->on('ICMT.incClassTaggedId', '=', 'ANNXC.anxtValue')
                        ->where('ANNXC.anxtType', 'CLASS_TYPE')
                        ->where('ANNXC.deletedFlag', 0);
                })
                ->leftJoin('annexture as ANNXG', function ($join) {
                    $join->on('ICM.gender', '=', 'ANNXG.anxtValue')
                        ->where('ANNXG.anxtType', 'GENDER')
                        ->where('ANNXG.deletedFlag', 0);
                })
                ->leftJoin('annexture as ANNXCA', function ($join) {
                    $join->on('ICM.caste', '=', 'ANNXCA.anxtValue')
                        ->where('ANNXCA.anxtType', 'CASTE')
                        ->where('ANNXCA.deletedFlag', 0);
                })
                ->leftJoin('annexture as ANNXD', function ($join) {
                    $join->on('ICM.disabilityType', '=', 'ANNXD.anxtValue')
                        ->where('ANNXD.anxtType', 'DISABILITY_TYPE')
                        ->where('ANNXD.deletedFlag', 0);
                })
                ->where('ICMT.deletedFlag', 0);
                if(!empty($request->input("incentiveId"))){
                    $queryData->where('incentiveName',trim($request->input("incentiveId")));
                }

            $queryData = $queryData->groupBy('ICM.incentiveConfigId','ANNXG.anxtName','ANNXCA.anxtName','ANNXD.anxtName')
                ->selectRaw("ICM.incentiveConfigId as incConfigId,ICM.ageMax,ICM.ageMin,ICM.belongsToBPL,group_concat(ICMT.incClassTaggedId) as classTaggedId,IM.incentiveName,group_concat(ANNXC.anxtName) as className,ANNXG.anxtName as genderName,ANNXCA.anxtName as casteName,ANNXD.anxtName as disabilityName");
            // $totalRecord = $queryData->count();
            $totalRecord = count($queryData->get());

              if($serviceType != "Download"){
                $offset         = (int)$request->input("offset") ? (int)$request->input("offset") : 0;
                $limit          = (int)$request->input("limit") ? (int)$request->input("limit") : $totalRecord;
                $queryData      = $queryData->offset($offset)->limit($limit);
            }
     
            //$responseData = $queryData->offset($offset)->limit($limit)->orderBy('incentiveName', 'ASC')->get();
            $responseData   = $queryData->orderBy('incentiveName', 'ASC')->get();
            if($serviceType == "Download"){  
                $userId = Crypt::decryptString($request->input("userId"));
               $downloadResponse = $this->downloadIncentiveCfgList($responseData, $userId);
   
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
              
            } 
            else {
                $i = $offset;
                    foreach ($responseData as $key => $value) {
                        $responseData[$key]->slNo = ++$i;
                        $responseData[$key]->encId = Crypt::encryptString($value->incConfigId);   
                    }
                    $status     = "SUCCESS";
                    $statusCode = config('constant.SUCCESS_CODE');  
            }
           
        }
    }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'incentiveConfigController',
                'Method' => 'viewIncentiveConfigData',
                'Error'  => $t->getMessage()
            ]);
            DB::rollback();
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
        ], $statusCode);
    }

    /* Created By : Nitish Nanda || Created On : 19-08-2022 || Service method Name : downloadIncentiveCfgList || Description: downloadIncentiveCfgList data  */
    private function downloadIncentiveCfgList($getCsvData, $userId)
    { 
        $status     = "ERROR";
        $statusCode = config('constant.EXCEPTION_CODE');
        $msg        = '';
        $responseData = '';  
        try { 
            $csvFileName = "IncentiveCfg_List_".$userId."_".time().".csv";
            $csvColumns = ["Sl. No", "Incentive Type", "Class","Gender","Caste","Agemax","AgeMin","Belongs to BPL","Type of disability"];

            $csvDataArr = array();
            $slno = 1;
            foreach($getCsvData as $csvData){
                $csvDataArr[] = [
                    $slno,
                    !empty($csvData->incentiveName) ? $csvData->incentiveName : '--',
                    !empty($csvData->className) ? $csvData->className : '--',
                    !empty($csvData->genderName) ? $csvData->genderName : '--',
                    !empty($csvData->casteName) ? $csvData->casteName : '--',
                    !empty($csvData->ageMax) ? $csvData->ageMax : '--',
                    !empty($csvData->ageMin) ? $csvData->ageMin : '--',
                    !empty($csvData->belongsToBPL == 2) ? 'No' : (($csvData->belongsToBPL == 1)? 'Yes': '--'), 
                     !empty($csvData->disabilityName) ? $csvData->disabilityName : '--',
                    
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
                'Controller' => 'incentiveConfigController',
                'Method' => 'downloadIncentiveCfgList',
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

    // Created By  :  Manoj Kumar Baliarsingh ||  Created On  : 04-06-2022 || Service method Name : getIncentiveName || Description:  Incentive Name list With Filter  
    public function getIncentiveName(Request $request)
    {
        $msg = '';        
        $responseData = '';
        try{   
            $status = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
            $queryData = IncentiveModel::select('incentiveId', 'incentiveName', 'incentiveCode', 'createdOn')->where('deletedflag', 0);

            $responseData = $queryData->get();
            if ($responseData->isEmpty()) {
                $msg = 'No record found.';
            } else {
                $response = array();
                foreach ($responseData  as $res) {
                    // $res['encId']                   = Crypt::encryptString($res->incentiveId);
                    $res['incentiveId']             = $res->incentiveId;
                    $res['incentiveName']           = $res->incentiveName;
                    $res['incentiveCode']           = $res->incentiveCode;
                    $res['createdOn']               = $res->createdOn;
                    array_push($response, $res);
                }
            }
        }
    catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'incentiveConfigController',
            'Method' => 'getIncentiveName',
            'Error'  => $t->getMessage()
        ]);
        $status = "ERROR";
        $statusCode = config('constant.EXCEPTION_CODE');
        $msg = config('constant.EXCEPTION_MESSAGE');               
    }
        return response()->json([
            "status"     => $status,
            "statusCode" => $statusCode,
            "msg"        => $msg,
            "data"       => $responseData
        ], $statusCode);
    }
}
