<?php
/**
    * Created By  : Swagatika
	* Created On  : 16-05-2022
	* Module Name : Appoint Subject Master Controller
	* Description : managing all appoint subject for add, view,delete,edit,search actions.
    * Modified By :  
    * Modified On : 
**/
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AppointSubjectModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointSubjectController extends Controller
{
    /* Created By  :  Swagatika ||  Created On  : 16-05-2022 || Service method Name : getAppointSubject || Description: Appoint Subject view according to filter  */
    public function getAppointSubject(Request $request)
    {
        $msg = ''; $statusCode = config('constant.SUCCESS_CODE');
        try{
        $queryData = AppointSubjectModel::select('appointSubId','subjectName','description','createdOn')->where('deletedflag',0);
        if (!empty($request->input("id"))) {
            $id  = Crypt::decryptString($request->input("id"));
            $queryData->where([['appointSubId', $id]]);
        }
        $responseData = $queryData->orderBy('subjectName', 'ASC')->get();
        if($responseData->isEmpty()){
            $msg = 'No record found.';
        }else{
            $response = array();
            foreach($responseData  as $res){
                $res['encId']   = Crypt::encryptString($res->appointSubId);
                array_push($response,$res);
            }
            $responseData = (count($response)>1)?$response:$response[0];
        }
    }
    catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'AppointSubjectController',
            'Method'     => 'getAppointSubject',
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
    
    /* Created By  :  Swagatika ||  Created On  : 16-05-2022 || Service method Name : AppointSubject || Description: Add Appoint Subject   */
    public function addAppointSubject(Request $req)
    {
        
        $status = "ERROR";
        DB::beginTransaction();
        try{
        if(!empty(request()->all())) {

            $validator = Validator::make($req->all(), [
                'subjectName' => 'required|max:120|unique:appointSubject,subjectName,'.',appointSubId,deletedFlag,0',
                'description' => 'max:500',
            ],[
                'subjectName.required' => 'Subject is mandatory.',
                'subjectName.max'      => 'Subject length should not be greater than 40 characters.',
                'subjectName.unique'   => 'The subject has already been taken.',
                'description.max'      => 'Subject length should not be greater than 500 characters.',
            ]);

            if($validator->fails()) {
                $errors = $validator->errors();
                $msg = array();
                foreach ($errors->all() as $message) {
                    $msg[] = $message;
                }
                $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
            }else{    
                $obj = new AppointSubjectModel();
                $obj->subjectName = $req->input("subjectName");
                $obj->description = $req->input("description");                
                $obj->createdBy   = (!empty($req->input("createdBy"))) ? $req->input("createdBy") : 0;
                if($obj->save()){
                    $status = "SUCCESS";
                    $statusCode = config('constant.SUCCESS_CODE');
                    $msg = "Appoint subject added successfuly";
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
            'Controller' => 'AppointSubjectController',
            'Method'     => 'addAppointSubject',
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
   
    /* Created By  :  Swagatika ||  Created On  : 16-05-2022 || Service method Name : updateAppointSubject || Description: Update Appoint Subject   */
    public function updateAppointSubject(Request $req)
    {
        $status = "ERROR";
        DB::beginTransaction();
        try{
        if(!empty(request()->all())) {
            $id  = Crypt::decryptString($req->input("encId"));
            $validator = Validator::make($req->all(), [
                'encId'       => 'required',
                'subjectName' => 'required|max:120|unique:appointSubject,subjectName,'.$id.',appointSubId,deletedFlag,0',
                'description' => 'max:500',
            ],[
                'encId.required'       => 'Appoint subject encryption id is mandatory.',
                'subjectName.required' => 'Subject is mandatory.',
                'subjectName.max'      => 'Subject length should not be greater than 40 characters.',
                'subjectName.unique'   => 'The subject has already been taken.',
                'description.max'      => 'Subject length should not be greater than 500 characters.',
            ]);
    
            if($validator->fails()) {
                $errors = $validator->errors();
                $msg = array();
                foreach ($errors->all() as $message) {
                    $msg[] = $message;
                }
                $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
            }else{    
                $dataArr['subjectName'] = trim($req->input("subjectName"));
                $dataArr['description'] = trim($req->input("description"));   
                $dataArr['updatedBy'] = (!empty($req->input("updatedBy"))) ? $req->input("updatedBy") : 0;         
                $dataArr['updatedOn']    = Carbon::now('Asia/Kolkata');

                $upObj = AppointSubjectModel::where('appointSubId',$id)->update($dataArr);
                if($upObj){
                    $status = "SUCCESS";
                    $statusCode = config('constant.SUCCESS_CODE');
                    $msg = "Appoint subject updated successfuly";
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
            'Controller' => 'AppointSubjectController',
            'Method'     => 'addAppointSubject',
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

    
    /* Created By  :  Swagatika ||  Created On  : 16-05-2022 || Service method Name : deleteAppointSubject || Description: Delete Appoint Subject   */
    public function deleteAppointSubject(Request $request)
    {
        $status = "ERROR";
        DB::beginTransaction();
        try{
        $id  = Crypt::decryptString($request->input("encId"));
        
        $dataArr['deletedFlag'] = 1;
        $dataArr['updatedOn']    = Carbon::now('Asia/Kolkata');
        $dataArr['updatedBy']   = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;

 

        $upObj = AppointSubjectModel::where('appointSubId',$id)->update($dataArr);
        if($upObj){
            $status = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
            $msg = "Appoint subject deleted successfuly";
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
            'Controller' => 'AppointSubjectController',
            'Method'     => 'addAppointSubject',
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
            'success'=>$success
        ],$statusCode);
    }

    /* Created By  :  Swagatika ||  Created On  : 16-05-2022 || Service method Name : viewAppointSubject || Description: Appoint Subject Listing and Search   */
    public function viewAppointSubject(Request $req)
    {
        $msg = '';
        $responseData   = '';    
      
        try{
        $status = "ERROR";
        $statusCode = config('constant.SUCCESS_CODE');
        $serviceType= $req->input("serviceType");
        $queryData = AppointSubjectModel::select('appointSubId','subjectName','description','createdOn')->where('deletedflag', 0);   
        if (!empty($req->input("appointSubId"))) {
            $queryData->where('appointSubId', trim($req->input("appointSubId")));
        }
        if (!empty($req->input("subjectName"))) {
            $queryData->where('subjectName', 'like', '%' . trim($req->input("subjectName")) . '%');
        }
        if (!empty($req->input("description"))) {
            $queryData->where('description', trim($req->input("description")));
        }
        $queryData = $queryData->where('deletedflag',0);
        $totalRecord = $queryData->count();

          if($serviceType != "Download"){
                $offset         = (int)$req->input("offset") ? (int)$req->input("offset") : 0;
                $limit          = (int)$req->input("limit") ? (int)$req->input("limit") : $totalRecord;
                $queryData      = $queryData->offset($offset)->limit($limit);
            }
        //$responseData = $queryData->offset($offset)->limit($limit)->orderBy('subjectName', 'ASC')->get(); 
        $responseData   = $queryData->orderBy('subjectName', 'ASC')->get();
        
        if($serviceType == "Download"){  
            $userId = Crypt::decryptString($req->input("userId"));
           $downloadResponse = $this->downloadAppSubjectList($responseData, $userId);

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
                    $responseData[$key]->encId = Crypt::encryptString($value->appointSubId);
                }
                $status     = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');     
        }
       }
    }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'AppointSubjectController',
                'Method' => 'viewAppointSubject',
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

     /* Created By : Nitish Nanda || Created On : 19-08-2022 || Service method Name : downloadAppSubjectList || Description: downloadAppSubjectList data  */
     private function downloadAppSubjectList($getCsvData, $userId)
     { 
         $status     = "ERROR";
         $statusCode = config('constant.EXCEPTION_CODE');
         $msg        = '';
         $responseData = '';  
         try { 
             $csvFileName = "AppointSub_List_".$userId."_".time().".csv";
             $csvColumns = ["Sl. No", "Subject", "Description","Created On"];
 
             $csvDataArr = array();
             $slno = 1;
             foreach($getCsvData as $csvData){
                 $csvDataArr[] = [
                     $slno,
                     !empty($csvData->subjectName) ? $csvData->subjectName : '--',
                     !empty($csvData->description) ? $csvData->description : '--',
                     !empty($csvData->createdOn) ? $csvData->createdOn : '--',
                     
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
                 'Controller' => 'AppointSubjectController',
                 'Method' => 'downloadAppSubjectList',
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
