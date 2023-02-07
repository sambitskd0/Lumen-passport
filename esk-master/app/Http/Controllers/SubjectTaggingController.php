<?php
/**
 * Created By  : Nitish Nanda
 * Created On  : 02-06-2022
 * Module Name : Master Module
 * Description : Manage Subject Tagging Add,View,Edit Details .
 **/

namespace App\Http\Controllers;
use App\Models\SubjectTaggingModel;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\AnnextureModel;
use App\Models\subjectModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;


class SubjectTaggingController extends Controller
{
    /* Created By  :  Nitish Nanda ||  Created On  : 08-02-2022 || Component Name :SubjectTagging || Description:Add SubjectTagging */
 public function addSubjectTagging(Request $request){
        
    $status = "ERROR";
    DB::beginTransaction();
    try{
    $arrData = $request->all();
             $classId  = ($request->input("class"));
             $streamId  = ($request->input("streamId") ? $request->input("streamId") : 0);
             $groupId  = ($request->input("groupId") ? $request->input("groupId") : 0);
     if (!empty($request->all())) {
        $validator = Validator::make($arrData, [
            'classId' => 'bail|required|numeric|unique:subjectTagging,classId,'.',subTagId,streamId,'.$streamId.',groupId,'.$groupId.',deletedFlag,0',
        ],
            [
            'classId.required'  => 'class is mandatory.', 
            'classId.unique'    => 'The class,stream,group combination has already been taken..', ]
           
 );

        if ($validator->fails()) {
            $errors = $validator->errors();
            $msg = array();
            foreach ($errors->all() as $message) {
                $msg[] = $message;
            }
            $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
        } else {
            $classId  = ($request->input("classId"));
            $subjectTagdata = array();
            foreach ($request->input('comSuTaggingArray')  as $res) {
                $temp['classId'] = $classId;
                $temp['streamId'] = (!empty( $request->input("streamId"))) ? $request->input("streamId") :0;
                $temp['groupId'] = (!empty($request->input("groupId"))) ? $request->input("groupId") :0;
                $temp['subjectId'] = $res;
                if(in_array($res,$request->input('optSuTaggingArray'))){
                   $temp['subjectType'] = $res;
                }else{
                   $temp['subjectType'] = 1;
                }
                array_push($subjectTagdata, $temp);
            }
           
            foreach ($request->input('optSuTaggingArray')  as $resp) {
               if($resp>0){ 
                   if(in_array($resp,$request->input('comSuTaggingArray'))){
                       $temp['classId'] = $classId;
                       $temp['streamId'] = (!empty( $request->input("streamId"))) ? $request->input("streamId") :0;
                       $temp['groupId'] = (!empty($request->input("groupId"))) ? $request->input("groupId") :0;
                       $temp['subjectId'] = $resp;
                       $temp['subjectType'] = 1;
                    } else{
                       $temp['classId'] = $classId;
                       $temp['streamId'] = (!empty($request->input("streamId"))) ?$request->input("streamId") :0;
                       $temp['groupId'] = (!empty($request->input("groupId"))) ? $request->input("groupId") :0;
                       $temp['subjectId'] = $resp;
                       $temp['subjectType'] = 2;
                    } 
                   if(!in_array( $temp,$subjectTagdata)){
                                          
                       array_push($subjectTagdata, $temp);
                   } 
               }
           }
            if (SubjectTaggingModel::insert($subjectTagdata)) {
                $status = "SUCCESS";
                $statusCode = 200;
                $msg = "Subject Tagging insert successfuly";
            } 
            else {
                $statusCode = config('constant.DB_EXCEPTION_CODE');
                $msg = "Something went wrong while storing the data";
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
        'Controller' => 'SubjectTaggingController',
        'Method'     => 'addSubjectTagging',
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
    //
     /* Created By  :  Nitish Nanda ||  Created On  : 02-06-2022 || Component Name : subject tagging || Description: view Subjecttagging */
    public function viewSubjectTagging(Request $request) 
    {
        $msg = '';
        try{
        $statusCode = config('constant.SUCCESS_CODE');
        $queryData= DB::table('subjectTagging as tag')
            ->leftJoin('subject as compSub',function($join){
            $join->on('tag.subjectId','compSub.subjectId')
            ->where('tag.deletedFlag',0)->where('tag.subjectType','=',1);
          })
          ->leftJoin('subject as optSub',function($join){
            $join->on('tag.subjectId','optSub.subjectId')
            ->where('tag.deletedFlag',0)
            ->where('tag.subjectType','=',2);
          })
          ->leftJoin('annexture as annex',function($join){
            $join->on('tag.classId','annex.anxtValue')
            ->where('tag.deletedFlag',0)
            ->where('annex.anxtType','=','CLASS_TYPE');
          })
          ->leftJoin('annexture as streamAnnex',function($join){
            $join->on('tag.streamId','streamAnnex.anxtValue')
            ->where('tag.deletedFlag',0)
            ->where('streamAnnex.anxtType','=','STREAM_TYPE');
          })
          ->leftJoin('annexture as groupAnnex',function($join){
            $join->on('tag.groupId','groupAnnex.anxtValue')
            ->where('tag.deletedFlag',0)
            ->where('groupAnnex.anxtType','=','STREAM_GROUP_TYPE');
          })
          ->where('tag.deletedFlag',0)
          ->groupBy('tag.classId','tag.streamId','tag.groupId','annex.anxtName','streamAnnex.anxtName','groupAnnex.anxtName')
          ->selectRaw('group_concat(compSub.subject) as subject,
           group_concat(optSub.subject) as opsubject,tag.classId,tag.streamId,tag.groupId,annex.anxtName as className,streamAnnex.anxtName as streamName,groupAnnex.anxtName as groupName');$responseData = $queryData->get();
      if (count($responseData) == 0) {
            $msg = 'No record found.';
        } else {
            $response = array();
            foreach ($responseData as $res) {
                $res->encId                          = Crypt::encryptString($res->classId);
                array_push($response, $res);
            }
            $responseData = (count($response) > 0) ? $response : $response[0];
        }
    }
    catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'SubjectTaggingController',
            'Method' => 'viewSubjectTagging',
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
        ], $statusCode);
    }

 /* Created By  :  Nitish Nanda ||  Created On  : 02-06-2022 || Component Name : subject tagging || Description: get Subjecttagging */
    public function getSubjectTagging(Request $request){
        //return $request;
        $msg = '';
        try{
        $statusCode = config('constant.SUCCESS_CODE');
        $compSubQueryData = SubjectTaggingModel::selectRaw('classId,streamId,groupId,group_concat(subjectId) as compSub')
        ->where('subjectType', 1)
        ->where('deletedFlag', 0)
        ->groupBy('classId','streamId','groupId');
        if (!empty($request->input("id"))) {
            $id = Crypt::decryptString($request->input("id"));
            $compSubQueryData->where('classId',$id);
        }
        if (!empty($request->input("streamId"))) {
            $compSubQueryData->where('streamId',$request->input("streamId"));
        }
        if (!empty($request->input("groupId"))) {
            $compSubQueryData->where('groupId',$request->input("groupId"));
        }
        $compSubResponseData = $compSubQueryData->first()->toArray();
       // return $compSubResponseData;
        $opSubQueryData = SubjectTaggingModel::selectRaw('classId,streamId,groupId,group_concat(subjectId) as opSub')
        ->where('subjectType', 2)
        ->where('deletedFlag', 0)
        ->groupBy('classId','streamId','groupId');
        if (!empty($request->input("id"))) {
            $id = Crypt::decryptString($request->input("id"));
            $opSubQueryData->where('classId',$id);
        }
        if (!empty($request->input("streamId"))) {
            $opSubQueryData->where('streamId',$request->input("streamId"));
        }
        if (!empty($request->input("groupId"))) {
            $opSubQueryData->where('groupId',$request->input("groupId"));
        }
        $opSubResponseData = $opSubQueryData->first()->toArray();
        // return  $opSubResponseData;
        // print_r($compSubResponseData);
        // print_r($opSubResponseData);
        $finalArr = array_merge($compSubResponseData,$opSubResponseData);
       // return  $finalArr;
            $response = array();
                //$response['encId']           = Crypt::encryptString($finalArr['classId']);
                $response['classId']         =(!empty($finalArr['classId'])) ?  trim($finalArr['classId']) :'';
                $response['streamId']         =(!empty($finalArr['streamId'])) ?  trim($finalArr['streamId']) :'';
                $response['groupId']         =(!empty($finalArr['groupId'])) ?  trim($finalArr['groupId']) :'';
                // $response['allSubTypeId']    = explode(',', $finalArr['compSub']);
                $response['allSubTypeId']    =array_map('intval', explode(',',$finalArr['compSub']));
                // $response['allOptTypeId']    = explode(',', $finalArr['opSub']);
                $response['allOptTypeId']    = array_map('intval', explode(',',$finalArr['opSub']));
            
           
// return $response;
    }
    catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'SubjectTaggingController',
            'Method'     => 'getSubjectTagging',
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
            "data" => $response
        ], $statusCode);
    }
 /* Created By  :  Nitish Nanda ||  Created On  : 02-06-2022 || Component Name : subject tagging || Description: update Subjecttagging */
    public function updateSubjectTagging(Request $request)
    {
        $status = "ERROR";
        DB::beginTransaction();
        try{
         $arrData = $request->all();
             $classId  = ($request->input("class"));
             $streamId  = ($request->input("streamId") ? $request->input("streamId") : 0);
             $groupId  = ($request->input("groupId") ? $request->input("groupId") : 0);
        if (!empty($request->all())) {
           $validator = Validator::make($arrData, [    
             ]);
                 if ($validator->fails()) {
                 $errors = $validator->errors();
                     $msg = array();
             foreach ($errors->all() as $message) {
                 $msg[] = $message;
             }
             $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
         } else { 
            $dataArr['deletedFlag'] = 1;
            $dataArr['updatedOn']   = date('Y-m-d h:i:s');

         $upObj = SubjectTaggingModel::where('classId',$classId)->where('streamId',$streamId)->where('groupId',$groupId)
         ->update($dataArr);
            
             if ($upObj) {
                 $subjectTagdata = array();
                 foreach ($request->input('subjectTaggingArray')  as $res) {
                     $temp['classId'] = $classId;
                     $temp['streamId'] = $streamId ;
                     $temp['groupId'] = $groupId;
                     $temp['subjectId'] = $res;
                     if(in_array($res,$request->input('optsubjectTaggingArray'))){
                        $temp['subjectType'] = $res;
                     }else{
                        $temp['subjectType'] = 1;
                     }
                     array_push($subjectTagdata, $temp);
                 }
                
                 foreach ($request->input('optsubjectTaggingArray')  as $resp) {
                    if($resp>0){ 
                        if(in_array($resp,$request->input('subjectTaggingArray'))){
                            
                            $temp['classId'] = $classId;
                            $temp['streamId'] = $streamId ;
                            $temp['groupId'] = $groupId;
                            $temp['subjectId'] = $resp;
                            $temp['subjectType'] = 1;
                         } else{
                            $temp['classId'] = $classId;
                            $temp['streamId'] = $streamId ;
                            $temp['groupId'] = $groupId;
                            $temp['subjectId'] =  $resp;
                            $temp['subjectType'] = 2;
                         } 
                        if(!in_array( $temp,$subjectTagdata)){
                                               
                            array_push($subjectTagdata, $temp);
                        } 
                    }
                }
                 if (SubjectTaggingModel::insert($subjectTagdata)) {
                     $status = "SUCCESS";
                     $statusCode = 200;
                     $msg = "Subject Tagging updated successfuly";
                 } 
             } else {
                 $statusCode = config('constant.DB_EXCEPTION_CODE');
                 $msg = "Something went wrong while storing the data";
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
            'Controller' => 'SubjectTaggingController',
            'Method'     => 'updateSubjectTagging',
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
/* Created By  :  Nitish Nanda ||  Created On  : 02-06-2022 || Component Name : subject tagging || Description: view SubjecName */  
    public function getSubjectName(Request $request){
        $msg = '';
        try{
        $statusCode = config('constant.SUCCESS_CODE');
        $queryData = SubjectTaggingModel::select('classId','subjectId','subjectType', 'createdOn')
        ->where([['deletedFlag', 0]]);
        if (!empty($request->input("id"))) {
            $id  = Crypt::decryptString($request->input("id"));
            $queryData->where([['classId', $id]]);
        }
        $queryData = $queryData->with(['annexture','subject']); 
        $responseData = $queryData->get();
       
        if ($responseData->isEmpty()) {
            $msg = 'No record found.';
        } else {
            $response = array();
           
            foreach ($responseData as $res) {
                $res['encId']               = Crypt::encryptString($res->classId);
                $res['subjectName']         = $res->subject['subject'];
                $res['anxtName']            = $res->annexture->anxtName;
                $res['createdOn']           = $res->createdOn;
                array_push($response, $res);
            }
            $responseData = (count($response) > 1) ? $response : $response[0];
            $subjectId = array();
            $optionalSubId = array();
            foreach ($responseData as $res1) {
                $resp['subjectId']            = $res1->subjectId;
                $ressp['optionalSubId']        = $res1->optionalSubId;
                array_push($subjectId, $resp);
                array_push($optionalSubId, $ressp);
            }
        }
    }
    catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'SubjectTaggingController',
            'Method'     => 'getSubjectName',
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
            "data" => $responseData,
            "subjectId"=>$subjectId,
            "optionalSubId"=>$optionalSubId
        ], $statusCode);
    }
   /* Created By  :  Nitish Nanda ||  Created On  : 02-06-2022 || Component Name : subject tagging || Description: view Subject in subjectTable */
public function getSubject(Request $request){
        $msg = '';
        try{
        $statusCode = config('constant.SUCCESS_CODE');
        $queryData = subjectModel::select('subjectId','subject' ,'createdOn')
        ->where([['deletedFlag', 0]]);
        $responseData = $queryData->get();
        
    
        if ($responseData->isEmpty()) {
            $msg = 'No record found.';
        } else {
            $response = array();
            foreach ($responseData as $res) {
                $res['encId']               = Crypt::encryptString($res->subjectId);
                $res['subjectId']           = $res->subjectId;
                $res['subject']             = $res->subject;
                $res['createdOn']           = $res->createdOn;
                array_push($response, $res);
            }
            $responseData =$response;
        

        }
    }
    catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'SubjectTaggingController',
            'Method'     => 'getSubject',
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
     /* Created By  :  Saubhagya Patra ||  Created On  : 17-06-2022 || Service Name : getSubjectAccordingToClass || Description: get subject according to class id for smart class */
    public function getSubjectAccordingToClass (Request $request){
        $msg = '';
        try{
        $statusCode = config('constant.SUCCESS_CODE');
         $queryData = SubjectTaggingModel::select('classId','subjectId');
        if(!empty($request->input("classId"))){
            $classId  = $request->input("classId");
            $queryData->where('classId',$classId);
         }
          $queryData = $queryData->with('subject'); 
          $responseData = $queryData->get();
            if($responseData->isEmpty()){
                $msg = 'No record found.';
            }else{
                $response = array();
                foreach($responseData  as $res){
                    $res['subjectName'] = $res->subject->subject;
                    array_push($response,$res);
                }
                $responseData = $response;
            }
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'SubjectTaggingController',
                'Method'     => 'getSubjectAccordingToClass',
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

    
}
