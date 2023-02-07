<?php

/**
 * Created By  : Nitish Nanda
 * Created On  : 24-06-2022
 * Module Name : Master Module
 * Description : Manage Mark Configuration Add,View,Edit,Delete
 **/

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\ExaminationMasterModel;
use App\Models\MarkConfigurationModel;
use App\Models\ExaminationmarkModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MarkConfigurationController extends Controller
{
 /* Created By  :  Nitish Nanda ||  Created On  : 19-07-2022 || Component Name :Mark Configuration || Description: getClassByTermId */
    public function getClassByTermId(Request $req)
    {
        $msg = '';
          $statusCode = 200;
        try{
        $queryData = ExaminationMasterModel::where('examinationTypeId', $req->input("examinationTypeId"))
            ->where('deletedflag', 0)
            ->selectRaw('examinationMasterId,examinationTypeId,classId')->get();
        if ($queryData->isEmpty()) {
            $msg = 'No record found.';
        } else {
            $response = array();
            foreach ($queryData  as $res) {
                $resp['encId']                 = Crypt::encryptString($res->examinationMasterId);
                $resp['examinationTypeId']     = $res->examinationTypeId;
                $resp['classId']               =  array_map('intval', explode(',', $res->classId));
                array_push($response, $resp);
            }
            $responseData = (count($response) > 0) ? $response : $response[0];
        }
    }
    catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'MarkConfigurationController',
            'Method'     => 'getClassByTermId',
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

    /* Created By  :  Nitish Nanda ||  Created On  : 19-07-2022 || Component Name :Mark Configuration || Description: get SubjectForMarkConfiguration */

    public function getSubjectForMarkConfiguration(Request $req)
    {
       // return $req;
        $msg = '';
        $responseData = '';
        $statusCode = config('constant.SUCCESS_CODE');
         try {
            if(!empty($req->input("examType"))){
        
                $examinationTypeId =  $req->input('examType'); 
             }
            $queryData = DB::table('subjectTagging as subtag')
            ->leftJoin('subject as sub', function ($join) {
                $join->on('subtag.subjectId', '=', 'sub.subjectId')->where('sub.deletedFlag', '=', 0);
            })
            ->leftJoin('markConfiguration as markconfig', function ($join) use($examinationTypeId) {
                $join->on('subtag.classId', '=','markconfig.classId');
                $join->on('subtag.streamId', '=','markconfig.streamId');
                $join->on('subtag.groupId', '=','markconfig.groupId')
                ->where('subtag.deletedFlag', '=', 0)
                ->where('markconfig.examinationTypeId','=',$examinationTypeId)
                ->where('markconfig.deletedFlag', '=', 0);
            })
            ->leftJoin('examinationMark  as exm', function ($join) {
                $join->on('subtag.subjectId', '=', 'exm.subjectId');
                $join->on('exm.markConfigurationId', '=','markconfig.markConfigurationId')
                ->where('exm.deletedFlag', '=', 0);
            })
             ->where('subtag.deletedFlag', '=', 0)
            ->selectRaw('markconfig.markConfigurationId,subtag.classId,subtag.streamId,subtag.groupId,sub.subjectId,exm.fullMark,exm.theoryMark,exm.practicalMark,exm.minPassMark,sub.subject');
            // if (!empty($req->input("examType"))) {
            //     $queryData->where('markconfig.examinationTypeId', $req->input("examType"));
            // }
            if (!empty($req->input("classId"))) {
                $queryData->where('subtag.classId', $req->input("classId"));
            }
            if (!empty($req->input("streamId"))) {
                $queryData->where('subtag.streamId', $req->input("streamId"));
            }
            if (!empty($req->input("groupId"))) {
                $queryData->where('subtag.groupId', $req->input("groupId"));
            } 
            $responseData = $queryData->get();
          // return $responseData;
            if ($responseData->isEmpty()) {
                $msg = 'No record found.';
            } else {
                $response = array();
                foreach ($responseData  as $res) {
                    $res->groupId   = (!empty($req->groupId))? $req->groupId:'';
                    $res->streamId  = (!empty($req->groupId))? $req->streamId:''; 
                    $res->classId  = (!empty($req->classId))? $req->classId:''; 
                    $res->examType  = (!empty($req->examType))? $req->examType:'';  
                    array_push($response, $res);
                }
                $responseData = $response;
            }
        } catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'MarkConfigurationController',
                'Method'     => 'getSubjectForMarkConfiguration',
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


    /* Created By  :  Nitish Nanda ||  Created On  : 07-06-2022 || Component Name :Mark Configuration || Description:Add MarkConfiguration */
    public function addMarkConfiguration(Request $req)
    {
        //return $req;
        $status = "ERROR";
        DB::beginTransaction();
        try{
            if(!empty(request()->all())) {
                $arrPageData = request()->all();
                if(!empty($req->input("examType"))){
                    $examType= $req->input("examType");
                }
                if(!empty($req->input("classId"))){
                    $classId= $req->input("classId");
                }
                if(!empty($req->input("streamId"))){
                    $streamId= $req->input("streamId");
                }else{
                    $streamId=0;
                }
                if(!empty($req->input("groupId"))){
                    $groupId= $req->input("groupId");
                }else{
                    $groupId=0;
                }
                $validator = MarkConfigurationModel::markconfigurationValidation($arrPageData,'');
                if($validator->fails()) {
                    $errors = $validator->errors();
                    $msg = array();
                    foreach ($errors->all() as $message) {
                        $msg[] = $message;
                    }
                    $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                }else{   
                    $checkParentRecord=MarkConfigurationModel::selectRaw('markConfigurationId')->where('examinationTypeId', $examType)
                    ->where('classId', $classId)
                    ->where('streamId', $streamId)
                    ->where('groupId', $groupId)->get();
                   //return $checkParentRecord;
                    if($checkParentRecord->isEmpty()){
                        // return 11111;
                        $newRecordInsertOnParent = new MarkConfigurationModel();
                        $newRecordInsertOnParent->examinationTypeId = (!empty($req->input("examType"))) ? $req->input("examType") :0;
                        $newRecordInsertOnParent->classId= (!empty($req->input("classId"))) ? $req->input("classId") :0;
                        $newRecordInsertOnParent->streamId = (!empty($req->input("streamId"))) ? $req->input("streamId") :0;
                        $newRecordInsertOnParent->groupId= (!empty($req->input("groupId"))) ? $req->input("groupId") :0;
                        $newRecordInsertOnParent->createdBy = 1;
                        $newRecordInsertOnParent->createdOn = Carbon::now('Asia/Kolkata');
                        if ($newRecordInsertOnParent->save()) {
                            $lastInsertMarkConfigurationId=$newRecordInsertOnParent->markConfigurationId;
                           // return $lastInsertMarkConfigurationId;
                            $examMarkDataArr = array();
                            foreach ($req->input('markConfigarray')  as $res) {
                            $temp['markConfigurationId'] =$lastInsertMarkConfigurationId;
                            $temp['subjectId'] =  (!empty($res["subjectId"])) ? $res["subjectId"] :0;
                            $temp['theoryMark'] =  (!empty($res["theoryMark"])) ? $res["theoryMark"] :0;
                            $temp['practicalMark'] = (!empty($res["practicalMark"])) ? $res["practicalMark"] :0;
                            $temp['minPassMark'] =  (!empty($res["minPassMark"])) ? $res["minPassMark"] :0;
                            $temp['fullMark'] = (!empty($res["fullMark"])) ? $res["fullMark"] :0;
                            $temp['createdBy'] = 1;
                            $temp['createdOn'] =  Carbon::now('Asia/Kolkata');
                            array_push($examMarkDataArr, $temp);
                               }
                               if (ExaminationmarkModel::insert($examMarkDataArr)) {    
                                   $status = "SUCCESS";
                                   $statusCode = 200;
                                   $msg = "Mark Configuration  added successfuly";
                               } else {
                                   $statusCode = config('constant.DB_EXCEPTION_CODE');
                                   $msg = "Something went wrong while storing the data.";
                               }
                        }else {
                            $statusCode = config('constant.DB_EXCEPTION_CODE');
                            $msg = "Something went wrong while deleting the data.";
                        }
                    }else{
                        $getParentRecordmarkConfigurationId=$checkParentRecord[0]->markConfigurationId;
                       // return $getParentRecordmarkConfigurationId;
                        $dataArr['deletedFlag'] = 1;
                        $dataArr['updatedBy'] = 1;
                        $dataArr['updatedOn'] =  Carbon::now('Asia/Kolkata');
                        $Obj = DB::table('examinationMark')->where('markConfigurationId',$getParentRecordmarkConfigurationId)->update($dataArr);
                        if ($Obj) {
                            $examMarkDataArr = array();
                            foreach ($req->input('markConfigarray')  as $res) {
                                   //return $res;
                            $temp['markConfigurationId'] =$getParentRecordmarkConfigurationId;
                            $temp['subjectId'] =  (!empty($res["subjectId"])) ? $res["subjectId"] :0;
                            $temp['theoryMark'] =  (!empty($res["theoryMark"])) ? $res["theoryMark"] :0;
                            $temp['practicalMark'] = (!empty($res["practicalMark"])) ? $res["practicalMark"] :0;
                            $temp['minPassMark'] =  (!empty($res["minPassMark"])) ? $res["minPassMark"] :0;
                            $temp['fullMark'] = (!empty($res["fullMark"])) ? $res["fullMark"] :0;
                            $temp['createdBy'] = 1;
                            $temp['createdOn'] =  Carbon::now('Asia/Kolkata');
                            array_push($examMarkDataArr, $temp);
                               }
                               if (ExaminationmarkModel::insert($examMarkDataArr)) {    
                                   $status = "SUCCESS";
                                   $statusCode = 200;
                                   $msg = "Mark Configuration  added successfuly";
                               } else {
                                   $statusCode = config('constant.DB_EXCEPTION_CODE');
                                   $msg = "Something went wrong while storing the data.";
                               }
                        } else {
                            $statusCode = config('constant.DB_EXCEPTION_CODE');
                            $msg = "Something went wrong while deleting the data.";
                        }
                    }  
                }
            }else{
                $statusCode = config('constant.REQUEST_ERROR_CODE');
                $msg = "Something went wrong, Please try later.";
            }
            DB::commit(); 
        }catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'MarkConfigurationController',
                'Method'     => 'addMarkConfiguration',
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
}
