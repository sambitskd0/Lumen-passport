<?php
/**
 * Created By  : Nitish Nanda
 * Created On  : 04-06-2022
 * Module Name : Master Module
 * Description : Manage Shift Master Add,View,Edit,Delete
 **/

namespace App\Http\Controllers;
use App\Models\shiftMasterModel;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ShiftMasterController extends Controller
{
    //
    /* Created By  :  Nitish Nanda ||  Created On  : 04-06-2022 || Component Name :ShiftMaster || Description:Add ShiftMaster */
    public function addShiftMaster(Request $request){
        
        $status = "ERROR";
        DB::beginTransaction();
        try{
        $arrData = $request->all();
        
        if (!empty($request->all())) {
            $validator = Validator::make($arrData, [
                'shift'  => 'required|unique:shiftMaster,shift,'.',shiftId,deletedFlag,0',
                'shiftStartTime' => 'required',
                'shiftEndTime' => 'required',
                'shiftEndTime' => 'after:shiftStartTime'
            ], [
                'shift.required'  => 'shift Category Name is mandatory.',
                'shiftStartTime.required'  => 'shiftStartTime Category Name is mandatory.',
                'shiftEndTime.required'  => 'shiftEndTime Category Name is mandatory.',
                'shift.unique'    => 'The  shift has already been taken..',
                 ]);

            if ($validator->fails()) {
                $errors = $validator->errors();
                $msg = array();
                foreach ($errors->all() as $message) {
                    $msg[] = $message;
                }
                $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
            } else {
                $obj = new shiftMasterModel();
                $obj->shift = trim($arrData['shift']);
                $obj->shiftStartTime = trim($arrData['shiftStartTime']);
                $obj->shiftEndTime=($arrData['shiftEndTime']);
                $obj->createdBy   = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
                if($obj->save()){
                    $status = "SUCCESS";
                    $statusCode = config('constant.SUCCESS_CODE');
                    $msg = "Shift Master added successfuly";
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
    catch(\Throwable $t){
        Log::error("Error", [
            'Controller' => 'ShiftMasterController',
            'Method'     => 'addShiftMaster',
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

     /* Created By  :  Nitish Nanda ||  Created On  : 04-06-2022 || Component Name :ShiftMaster || Description:view ShiftMaster */
    public function viewShiftMaster(Request $request){
        $msg = '';
        try{
        $statusCode = config('constant.SUCCESS_CODE');
        $queryData = ShiftMasterModel::select('shiftId','shift','shiftStartTime','shiftEndTime','createdOn')->where([['deletedflag',0]]);

        if(!empty($request->input("shift"))){
            $queryData->where([['shift',trim($request->input("shift"))]]);
        }
        if(!empty($request->input("shiftStartTime"))){
            $queryData->where([['shiftStartTime',trim($request->input("shiftStartTime"))]]);
        }
        if(!empty($request->input("shiftEndTime"))){
            $queryData->where([['shiftEndTime',trim($request->input("shiftEndTime"))]]);
        }
        $responseData = $queryData->get();
        if($responseData->isEmpty()){
            $msg = 'No record found.';
        }else{
            $response = array();
            foreach($responseData  as $res){
                $res['encId']       = Crypt::encryptString($res->shiftId);
                if($res->shift == 1){
                    $res['shift']  ="Morning";
                   }
                   elseif($res->shift == 2){
                    $res['shift'] ="After Noon";
                   }
                   elseif($res->shift == 3){
                    $res['shift'] ="Day";
                   }
                $res['shiftStartTime']     = $res->shiftStartTime;
                $res['shiftEndTime']     = $res->shiftEndTime;
                $res['createdOn']   = $res->createdOn;
                array_push($response,$res);
            }
        }
    }
    catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'ShiftMasterController',
            'Method' => 'viewShiftMaster',
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
            "data" => $responseData
        ],$statusCode);
    }
/* Created By  :  Nitish Nanda ||  Created On  : 04-06-2022 || Component Name :ShiftMaster || Description:get ShiftMaster */
    public function getShiftMaster(Request $req ) 
    {
        $msg = '';
        try{
        $statusCode = config('constant.SUCCESS_CODE');
        $queryData = ShiftMasterModel::select('shiftId','shift','shiftStartTime','shiftEndTime','createdOn')
        ->where([['deletedFlag', 0]]);
        if (!empty($req->input("id"))) {
            $id  = Crypt::decryptString($req->input("id"));
            $queryData->where([['shiftId', $id]]);
        }
        $responseData = $queryData->get();
        
       
        if ($responseData->isEmpty()) {
            $msg = 'No record found.';
        } else {
            $response = array();
            foreach ($responseData as $res) {
                $res['encId']               = Crypt::encryptString($res->shiftId);
                $res['shift']        = $res->shift;
                $res['shiftStartTime']        = $res->shiftStartTime;
                $res['shiftEndTime']        = $res->shiftEndTime;
                $res['createdOn']               = $res->createdOn;
                array_push($response, $res);
            }
            $responseData = (count($response) > 1) ? $response : $response[0];
        }
    }
    catch(\Throwable $t){
        Log::error("Error", [
            'Controller' => 'ShiftMasterController',
            'Method'     => 'getShiftMaster',
            'Error'      => $t->getMessage()
        ]);
        DB::rollback();
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

  /* Created By  :  Nitish Nanda ||  Created On  : 04-06-2022 || Component Name :ShiftMaster || Description:update ShiftMaster */  
    public function updateShiftMaster(Request $request)
    {
        
        $status = "ERROR";
        DB::beginTransaction();
        try{
        $arrData = $request->all();
        $id  = Crypt::decryptString($request->input("encId"));
        if (!empty($request->all())) {
            $validator = Validator::make($arrData, [
                'shift'  => 'required|unique:shiftMaster,shift,'.$id.',shiftId,deletedFlag,0',
                'shiftStartTime'  => 'required',
                'shiftEndTime'  => 'required',
            ], [
                'shift.required'  => 'shift is mandatory.',
                'shiftStartTime.required'  => 'shiftStartTime  is mandatory.',
                'shiftEndTime.required'  => 'shiftEndTime  is mandatory.',
                'shift.unique'    => 'The  shift has already been taken..',
                ]);
 if ($validator->fails()) {
                $errors = $validator->errors();
                $msg = array();
                foreach ($errors->all() as $message) {
                    $msg[] = $message;
                }
                $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
            } else { 
               $dataArr['shift']      = trim($arrData['shift']);
                $dataArr['shiftStartTime']      = trim($arrData['shiftStartTime']);
                $dataArr['shiftEndTime']      = trim($arrData['shiftEndTime']);
                $dataArr['updatedOn']           = Carbon::now('Asia/Kolkata');
                $dataArr['updatedBy']           = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
                $upObj = ShiftMasterModel::where('shiftId',$id)->update($dataArr);
              
                if ($upObj) {
                    $status = "SUCCESS";
                    $statusCode = config('constant.SUCCESS_CODE');
                    $msg = "Shift Master Updated successfuly";
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
    catch(\Throwable $t){
        Log::error("Error", [
            'Controller' => 'ShiftMasterController',
            'Method'     => 'updateShiftMaster',
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


     /* Created By  :  Nitish Nanda ||  Created On  : 04-06-2022 || Component Name :ShiftMaster || Description:Delete ShiftMaster */
    public function deleteShiftMaster(Request $request)
     {
          $status = "ERROR";
          DB::beginTransaction();
        try{
         $id = Crypt::decryptString($request->input("encId"));
         $obj = ShiftMasterModel::find($id);
         $dataArr['deletedFlag'] = 1;
         $dataArr['updatedOn']    = Carbon::now('Asia/Kolkata');
         $dataArr['updatedBy']   = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;

         $upObj = ShiftMasterModel::where('shiftId',$id)->update($dataArr);
         if ($upObj) {
             $status = "SUCCESS";
             $statusCode = config('constant.SUCCESS_CODE');
             $msg = "Shift Master Details deleted successfully";
             $success = true;
         } else {
             $statusCode = 402;
             $msg = "Something went wrong while deleting the data.";
             $success = false;
         }
         DB::commit();
        }
        catch(\Throwable $t){
            Log::error("Error", [
                'Controller' => 'ShiftMasterController',
                'Method'     => 'deleteShiftMaster',
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
             'success'=>$success
         ], $statusCode);
     }
}
