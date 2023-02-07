<?php
/**
 * Created By  : saubhagya ranjan patra
 * Created On  : 06-05-2022
 * Module Name : manage block
 * Description : block add, view,delete,edit,search actions.
 **/
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\RedisController;
use App\Models\BlockModel;
use App\Models\DistrictModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BlockController extends Controller
{
    /* Created By  :  Saubhgya Ranjan Patra ||  Created On  : 06-05-2022 || Service Method Name : createBlock || Description: Block creating  */
    public function addBlock(Request $req)
    {     
        $status = "ERROR";
        DB::beginTransaction();
        try {
            if (!empty(request()->all())) {

                $validator = Validator::make($req->all(), [
                    'districtId' => 'required',
                    'blockName' => 'required|max:40|unique:blocks,blockName,' . ',blockId,districtId,' . $req->input("districtId") . ',deletedFlag,0',
                    'blockCode' => 'required|digits_between:1,15|unique:blocks,blockCode,' . ',blockId,deletedFlag,0',
                ], [
                    'districtId.required' => 'District id is mandatory.',
                    'blockName.required' => 'Block name is mandatory.',
                    'blockName.max' => 'Block name length should not be greater than 40 characters.',
                    'blockName.unique' => 'The combination of district and block name has already been taken.',
                    'blockCode.required' => 'Block code is mandatory.',
                    'blockCode.digits_between' => 'Block code length should not be greater than 5 digits.',
                    'blockCode.unique' => 'Block code has already been taken.',
                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors();
                    $msg = array();
                    foreach ($errors->all() as $message) {
                        $msg[] = $message;
                    }
                    $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                } else {
                    $districrtDetails = DistrictModel::selectRaw('districtCode,districtName')->where('districtId', $req->input("districtId"))
                        ->where('deletedFlag', 0)->get();
                    $obj = new BlockModel();
                    $obj->districtName      = $districrtDetails[0]->districtName;
                    $obj->districtCode      = $districrtDetails[0]->districtCode;
                    $obj->districtId        = $req->input("districtId");
                    $obj->blockName         = $req->input("blockName");
                    $obj->blockCode         = $req->input("blockCode");
                    $obj->createdBy         = (!empty($req->input("userId"))) ? Crypt::decryptString($req->input("userId")) : 0;
                    if ($obj->save()) {
                        RedisController::setBlockListRedis();
                        $status = "SUCCESS";
                        $statusCode = 200;
                        $msg = "Block added successfuly";
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
        } catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'BlockController',
                'Method' => 'addBlock',
                'Error' => $t->getMessage(),
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
        ], $statusCode);
    }

    /* Created By  :  Saubhgya Ranjan Patra ||  Created On  : 06-05-2022 || Service Method Name : updateBlock || Description: Updata Block data  */
    public function updateBlock(Request $req)
    {
        $status = "ERROR";
        DB::beginTransaction();
        try {
            if (!empty(request()->all())) {
                $id = Crypt::decryptString($req->input("encId"));
                $validator = Validator::make($req->all(), [
                    'encId' => 'required',
                    'districtId' => 'required',
                    'blockName' => 'required|max:40|unique:blocks,blockName,' . $id . ',blockId,districtId,' . trim($req->input("districtId")) . ',deletedFlag,0',
                    'blockCode' => 'required|digits_between:1,15|unique:blocks,blockCode,' . $id . ',blockId,deletedFlag,0',
                ], [
                    'encId.required' => 'Block encryption id is mandatory.',
                    'districtId.required' => 'District is mandatory.',
                    'blockName.required' => 'Block name is mandatory.',
                    'blockName.max' => 'Block name length should not be greater than 40 characters.',
                    'blockName.unique' => 'The combination of district and block name has already been taken.',
                    'blockCode.required' => 'Block code is mandatory.',
                    'blockCode.digits_between' => 'Block code length should not be greater than 5 digits.',
                    'blockCode.unique' => 'Block code has already been taken.',
                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors();
                    $msg = array();
                    foreach ($errors->all() as $message) {
                        $msg[] = $message;
                    }
                    $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                } else {
                    $districrtDetails = DistrictModel::selectRaw('districtCode,districtName')->where('districtId', $req->input("districtId"))
                        ->where('deletedFlag', 0)->get();
                    $dataArr['districtName']    = $districrtDetails[0]->districtName;
                    $dataArr['districtCode']    = $districrtDetails[0]->districtCode;
                    $dataArr['districtId']      = trim($req->input("districtId"));
                    $dataArr['blockName']       = trim($req->input("blockName"));
                    $dataArr['blockCode']       = trim($req->input("blockCode"));
                    $dataArr['updatedOn']       = Carbon::now('Asia/Kolkata');
                    $dataArr['updatedBy']       = (!empty($req->input("userId"))) ? Crypt::decryptString($req->input("userId")) : 0;

                    $upObj = BlockModel::where('blockId', $id)->update($dataArr);
                    if ($upObj) {
                        RedisController::setBlockListRedis();
                        $status = "SUCCESS";
                        $statusCode = 200;
                        $msg = "Block updated successfuly";
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

        } catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'BlockController',
                'Method' => 'updateBlock',
                'Error' => $t->getMessage(),
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
        ], $statusCode);
    }

    /* Created By  :  Saubhgya Ranjan Patra ||  Created On  : 06-05-2022 || Service Method Name : deleteBlock || Description: Delete Block */
    public function deleteBlock(Request $request)
    {
        $status = "ERROR";
        DB::beginTransaction();
        try {
            $id = Crypt::decryptString($request->input("encId"));
            $dataArr['deletedFlag'] = 1;
            $dataArr['updatedOn'] = Carbon::now('Asia/Kolkata');
            $dataArr['updatedBy']   = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;

            $upObj = BlockModel::where('blockId', $id)->update($dataArr);
            if ($upObj) {
                RedisController::setBlockListRedis();
                $status = "SUCCESS";
                $statusCode = 200;
                $msg = "Block deleted successfully";
                $success = true;
            } else {
                $statusCode = config('constant.DB_EXCEPTION_CODE');
                $msg = "Something went wrong while deleting the data.";
                $success = false;
            }
            DB::commit();
        } catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'BlockController',
                'Method' => 'deleteBlock',
                'Error' => $t->getMessage(),
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
            "success" => $success,
            //"data" => $responseData
        ], $statusCode);
    }

    /* Created By  :  Saubhgya Ranjan Patra ||  Created On  : 04-05-2022 || Service method Name : viewDistrict || Description: Block Listing and Search   */
    public function viewBlock(Request $req)
    {        
        $msg = '';
        $responseData   = '';
        try{  
        $status     = "ERROR"; 
        $statusCode = 200;
        $serviceType= $req->input("serviceType");
        $queryData = BlockModel::select('blockId', 'districtId', 'blockName', 'blockCode')->where('deletedflag', 0);

        if (!empty($req->input("blockId"))) {
            $queryData->where('blockId', trim($req->input("blockId")));
        }
        if (!empty($req->input("districtId"))) {
            $queryData->where('districtId', trim($req->input("districtId")));
        }
        if (!empty($req->input("blockName"))) {
            $queryData->where('blockName', 'like', '%' . trim($req->input("blockName")) . '%');
        }
        if (!empty($req->input("blockCode"))) {
            $queryData->where('blockCode', trim($req->input("blockCode")));
        }

        $queryData = $queryData->with('district');

        $totalRecord = $queryData->count();

        if($serviceType != "Download"){
            $offset         = (int)$req->input("offset") ? (int)$req->input("offset") : 0;
            $limit          = (int)$req->input("limit") ? (int)$req->input("limit") : $totalRecord;
            $queryData      = $queryData->offset($offset)->limit($limit);
        }

        $responseData   = $queryData->orderBy('blockName', 'ASC')->get();
        if($serviceType == "Download"){  
            $userId = Crypt::decryptString($req->input("userId"));
            $downloadResponse = $this->downloadBlockList($responseData, $userId);

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
            $responseData = $queryData->get();
            if ($responseData->isEmpty()) {
                $msg = 'No record found.';
            
            } else {
                $i = $offset;
                foreach ($responseData as $key => $value) {
                    $responseData[$key]->slNo = ++$i;
                    $responseData[$key]->encId = Crypt::encryptString($value->blockId);
                    $responseData[$key]->districtName =$value->district->districtName;
                }            
                $status     = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
            }
        }
    }
    catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'BlockController',
            'Method' => 'viewBlock',
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
            "totalRecord" => $totalRecord
        ], $statusCode);
    }
      /* Created By : Nitish Nanda || Created On : 19-08-2022 || Service method Name : downloadBlockList || Description: downloadBlockList data  */
      private function downloadBlockList($getCsvData, $userId)
      { 
        
        $status     = "ERROR";
        $statusCode = config('constant.EXCEPTION_CODE');
        $msg        = '';
        $responseData = '';  
        try { 
            $csvFileName = "Block_List_".$userId."_".time().".csv";
            $csvColumns = ["Sl. No", "District Name","Block Name", "Block Code"];

            $csvDataArr = array();
            $slno = 1;
            foreach($getCsvData as $csvData){
                $csvDataArr[] = [
                    $slno,
                    !empty($csvData->district->districtName) ? $csvData->district->districtName : '--',
                    !empty($csvData->blockName) ? $csvData->blockName : '--',
                    !empty($csvData->blockCode) ? $csvData->blockCode : '--',
                    
                ];
                $slno++;
            }
              
            $downloadResponse = $this->downloadCsv($csvFileName, $csvColumns, $csvDataArr);

            if($downloadResponse['statusCode'] == 200){
                $status = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $responseData = $downloadResponse['data'];  
            } else {
                $msg = 'Could not create and download file.';
            }
        } catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'BlockController',
                'Method' => 'downloadBlockList',
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

    /* Created By  :  Saubhgya Ranjan Patra ||  Created On  : 06-05-2022 || Service Method Name : getBlockById || Description: View of Block And search filter  */
    public function getBlockById(Request $request)
    {
        $msg = '';
        $statusCode = 200;
        try {
            $queryData = BlockModel::select('blockId', 'districtId', 'blockName', 'blockCode')->where('deletedflag', 0);
            if (!empty($request->input("id"))) {
                $id  = Crypt::decryptString($request->input("id"));
                $queryData->where([['blockId', $id]]);
            }
            $queryData = $queryData->with('district');

            $responseData = $queryData->orderBy('blockName', 'ASC')->get();

            if ($responseData->isEmpty()) {
                $msg = 'No record found.';
            } else {
                $response = array();
                foreach ($responseData as $res) {
                    $res['encId'] = Crypt::encryptString($res->blockId);
                    $tmp['districtName'] = $res->district->districtName;
                    array_push($response, $res);
                }
                $responseData = (count($response) > 1) ? $response : $response[0];
            }
        } catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'BlockController',
                'Method' => 'getBlock',
                'Error' => $t->getMessage(),
            ]);
            $status = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg = config('constant.EXCEPTION_MESSAGE');
        }
        return response()->json([
            "status" => 'SUCCESS',
            "statusCode" => $statusCode,
            "msg" => $msg,
            "data" => $responseData,
        ], $statusCode);
    }

    
}
