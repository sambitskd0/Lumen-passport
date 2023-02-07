<?php

/**
 * Created By  : saubhagya ranjan patra
 * Created On  : 04-05-2022
 * Module Name : District Master Controller
 * Description : managing all district master for add, view,delete,edit,search actions.
 * Modified By: Swagatika Sahoo
 * Modified On: 12-05-2022
 **/

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\RedisController;
use App\Models\DistrictModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class DistrictController extends Controller
{  
    /* Created By  :  Saubhgya Ranjan Patra ||  Created On  : 04-05-2022 || Service method Name : create || Description: Add District   */
    public function addDistrict(Request $req)
    {
        $status = "ERROR";
        DB::beginTransaction();
        try{
            if (!empty(request()->all())) {

                $validator = Validator::make($req->all(), [
                    'districtName' => 'required|max:40|unique:districts,districtName,' . ',districtId,deletedFlag,0',
                    'districtCode' => 'required|digits_between:1,15|unique:districts,districtCode,' . ',districtId,deletedFlag,0',
                ], [
                    'districtName.required' => 'District name is mandatory.',
                    'districtName.max'      => 'District name length should not be greater than 40 characters.',
                    'districtName.unique'   => 'The district name has already been taken.',
                    'districtCode.required' => 'District code is mandatory.',
                    'districtCode.digits_between' => 'District code length should not be greater than 5 digits.',
                    'districtCode.unique'         => 'The district code has already been taken.',
                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors();
                    $msg = array();
                    foreach ($errors->all() as $message) {
                        $msg[] = $message;
                    }
                    $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                } else {
                    $obj = new DistrictModel();
                    $obj->districtName = $req->input("districtName");
                    $obj->districtCode = $req->input("districtCode");
                    $obj->createdBy    = (!empty($req->input("createdBy"))) ? $req->input("createdBy") : 0;
                    $obj->createdOn    = Carbon::now('Asia/Kolkata');
                    if ($obj->save()) {
                        RedisController::setDistrictListRedis();
                        $status = "SUCCESS";
                        $statusCode = config('constant.SUCCESS_CODE');
                        $msg = "District added successfuly";
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
        } catch(\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'DistrictController',
                'Method'     => 'addDistrict',
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

    /* Created By  :  Saubhgya Ranjan Patra ||  Created On  : 04-05-2022 || Service method Name : updateDist || Description: Update district District   */
    public function updateDistrict(Request $req)
    {
        $status = "ERROR";
        DB::beginTransaction();
        try{
            if (!empty(request()->all())) {
                $id  = Crypt::decryptString($req->input("encId"));
                $validator = Validator::make($req->all(), [
                    'encId'        => 'required',
                    'districtName' => 'required|max:40|unique:districts,districtName,' . $id . ',districtId,deletedFlag,0',
                    'districtCode' => 'required|digits_between:1,15|unique:districts,districtCode,' . $id . ',districtId,deletedFlag,0',
                ], [
                    'encId.required'        => 'District encryption id is mandatory.',
                    'districtName.required' => 'District name is mandatory.',
                    'districtName.max'      => 'District name length should not be greater than 40 characters.',
                    'districtName.unique'   => 'The district name has already been taken.',
                    'districtCode.required' => 'District code is mandatory.',
                    'districtCode.digits_between' => 'District code length should not be greater than 5 digits.',
                    'districtCode.unique'         => 'The district code has already been taken.',
                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors();
                    $msg = array();
                    foreach ($errors->all() as $message) {
                        $msg[] = $message;
                    }
                    $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                } else {

                    $dataArr['districtName'] = trim($req->input("districtName"));
                    $dataArr['districtCode'] = trim($req->input("districtCode"));
                    $dataArr['updatedBy'] = (!empty($req->input("updatedBy"))) ? $req->input("updatedBy") : 0;
                    $dataArr['updatedOn'] = Carbon::now('Asia/Kolkata');

                    $upObj = DistrictModel::where('districtId', $id)->update($dataArr);
                    if ($upObj) {
                        RedisController::setDistrictListRedis();
                        $status = "SUCCESS";
                        $statusCode = config('constant.SUCCESS_CODE');
                        $msg = "District updated successfuly";
                    } else {
                        $statusCode = config('constant.DB_EXCEPTION_CODE');
                        $msg = "Something went wrong while updating the data.";
                    }
                }
            } else {
                $statusCode = config('constant.REQUEST_ERROR_CODE');
                $msg = "Something went wrong, Please try later.";
            }
            DB::commit(); 
        } catch(\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'DistrictController',
                'Method'     => 'updateDistrict',
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

    /* Created By  :  Saubhgya Ranjan Patra ||  Created On  : 04-05-2022 || Service method Name : deleteDist || Description: Delete District   */
    public function deleteDistrict(Request $req)
    {
        $status = "ERROR";
        DB::beginTransaction();
        try{
            $id = Crypt::decryptString($req->input("encId"));
            $dataArr['deletedFlag'] = 1;
            $dataArr['updatedBy'] =(!empty($req->input("updatedBy"))) ? $req->input("updatedBy") : 0;
            $dataArr['updatedOn'] =Carbon::now('Asia/Kolkata');

            $upObj = DistrictModel::where('districtId', $id)->update($dataArr);
            if ($upObj) {
                RedisController::setDistrictListRedis();
                $status = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $msg = "District deleted successfuly";
                $success = true;
            } else {
                $statusCode = config('constant.DB_EXCEPTION_CODE');
                $msg = "Something went wrong while deleting the data.";
                $success = false;
            }
            DB::commit(); 
        } catch(\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'DistrictController',
                'Method'     => 'deleteDistrict',
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
            "msg" => $msg,
            "success" => $success
        ], $statusCode);
    }

    /* Created By  :  Saubhgya Ranjan Patra ||  Created On  : 04-05-2022 || Service method Name : viewDistrict || Description: District Listing and Search   */
    public function viewDistrict(Request $req)
    {
        $msg            = '';
        $responseData   = '';    
        
        try{
            $status     = "ERROR";
            $statusCode = config('constant.SUCCESS_CODE');
            $serviceType= $req->input("serviceType");
            $queryData = DistrictModel::select('districtId', 'districtName', 'districtCode')->where('deletedflag', 0);   

            if (!empty($req->input("districtId"))) {
                $queryData->where('districtId', trim($req->input("districtId")));
            }

            if (!empty($req->input("districtName"))) {
                $queryData->where('districtName', 'like', '%' . trim($req->input("districtName")) . '%');
            }

            if (!empty($req->input("districtCode"))) {
                $queryData->where('districtCode', trim($req->input("districtCode")));
            }

            $queryData = $queryData->where('deletedflag',0);
            $totalRecord = $queryData->count();

            if($serviceType != "Download"){
                $offset         = (int)$req->input("offset") ? (int)$req->input("offset") : 0;
                $limit          = (int)$req->input("limit") ? (int)$req->input("limit") : $totalRecord;
                $queryData      = $queryData->offset($offset)->limit($limit);
            }
        
            $responseData   = $queryData->orderBy('districtName', 'ASC')->get();

            if($serviceType == "Download"){  
                $userId = Crypt::decryptString($req->input("userId"));
                $downloadResponse = $this->downloadDistrictList($responseData, $userId);

            if($downloadResponse['statusCode'] == 200){                    
                $responseData   = $downloadResponse['data'];  
                $status         = "SUCCESS";
                $statusCode     = config('constant.SUCCESS_CODE');
            } else {
                $responseData   = "";
                $msg = 'Could not create and download file.';
            }
        } 
        else {          
            if ($responseData->isEmpty()) {
                $msg = 'No record found.';
                // $success = true;    
            } 
            else {
                $i = $offset;
                    foreach ($responseData as $key => $value) {
                        $responseData[$key]->slNo = ++$i;
                        $responseData[$key]->encId = Crypt::encryptString($value->districtId);
                    }
                    $status     = "SUCCESS";
                    $statusCode = config('constant.SUCCESS_CODE');
                }
            }
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'DistrictController',
                'Method' => 'viewDistrict',
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

    /* Created By : Nitish Nanda || Created On : 19-08-2022 || Service method Name : downloadDistrict || Description: Download sub category data  */
    private function downloadDistrictList($getCsvData, $userId)
    { 
        $status     = "ERROR";
        $statusCode = config('constant.EXCEPTION_CODE');
        $msg        = '';
        $responseData = '';  
        try { 
            $csvFileName = "District_List_".$userId."_".time().".csv";
            $csvColumns = ["Sl. No", "District Name", "District Code"];

            $csvDataArr = array();
            $slno = 1;
            foreach($getCsvData as $csvData){
                $csvDataArr[] = [
                    $slno,
                    !empty($csvData->districtName) ? $csvData->districtName : '--',
                    !empty($csvData->districtCode) ? $csvData->districtCode : '--',                    
                ];
                $slno++;
            }
            
            $downloadResponse = $this->downloadCsv($csvFileName, $csvColumns, $csvDataArr);
            // dd( $downloadResponse);
            if($downloadResponse['statusCode'] == 200){
                $status = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $responseData = $downloadResponse['data'];  
            } else {
                $msg = 'Could not create and download file.';
            }
        } catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'DistrictController',
                'Method' => 'downloadDistrictList',
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

    /* Created By  :  Deepti Ranjan Dash ||  Created On  : 30-08-2022 || Service method Name : getDistrictById || Description: District details for edit  */
    public function getDistrictById(Request $request)
    {
        $msg = '';
        $statusCode = config('constant.SUCCESS_CODE');
        try{
            $queryData = DistrictModel::select('districtId', 'districtName', 'districtCode')->where('deletedflag', 0);
            if (!empty($request->input("id"))) {
                $id  = Crypt::decryptString($request->input("id"));
                $queryData->where([['districtId', $id]]);
            }
            $responseData = $queryData->orderBy('districtName', 'ASC')->get();
            if ($responseData->isEmpty()) {
                $msg = 'No record found.';
            } else {
                $response = array();
                foreach ($responseData  as $res) {
                    $res['encId']   = Crypt::encryptString($res->districtId);
                    array_push($response, $res);
                }
                $responseData = (count($response) > 1) ? $response : $response[0];
            }
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'DistrictController',
                'Method'     => 'getDistrict',
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

    
}
