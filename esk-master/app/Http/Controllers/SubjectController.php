<?php
/**
 * Created By  : Nitish Nanda
 * Created On  : 20-05-2022
 * Module Name : Master Module
 * Description : Manage Subject Details Add, View, Update, Delete, Tagging.
 **/
namespace App\Http\Controllers;
use App\Models\subjectModel;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;


class subjectcontroller extends Controller
{
    /* Created By  :  Nitish Nanda ||  Created On  : 20-05-2022 || Service method Name :saveusers  || Description:  Add subject Category  */
    public function addSubjectCategory(Request $request){
        
        $status = "ERROR";
        DB::beginTransaction();
        try{
        $arrData = $request->all();
        
        if (!empty($request->all())) {
            $validator = Validator::make($arrData, [
               
                'subject'  => 'required|max:20|unique:subject,subject,'.',subjectId,deletedFlag,0',
                'description'=>'max:300'
            ], [
                'subject.required'  => 'subject  Name is mandatory.',
                'subject.max'       => 'subject  Name length exceded Max Limit.',
                'subject.unique'    => 'The subject Name has already been taken..',
                'description.max'       => 'description  Name length exceded Max Limit.',
                
            
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors();
                $msg = array();
                foreach ($errors->all() as $message) {
                    $msg[] = $message;
                }
                $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
            } else {
                $obj = new subjectModel();
                $obj->subject = trim($arrData['subject']);
                $obj->description = trim($arrData['description']);
                $obj->createdBy   = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;  
                if($obj->save()){
                    $status = "SUCCESS";
                    $statusCode = config('constant.SUCCESS_CODE');
                    $msg = "Subject added successfuly";
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
            'Controller' => 'subjectcontroller',
            'Method'     => 'addSubjectCategory',
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
     // Created By  :  Nitish Nanda ||  Created On  : 20-05-2022 || Service method Name :viewSubjectCategory  || Description:  View subject category details list  
    public function viewSubjectCategory(Request $request) 
    {
       
        $msg = '';
        $responseData   = '';    
        
        try{
        $status = "ERROR";
        $statusCode = config('constant.SUCCESS_CODE');
        $serviceType= $request->input("serviceType");
        $queryData = subjectModel::select('subjectId','subject','description','createdOn')->where('deletedflag', 0);   
        if (!empty($request->input("subjectId"))) {
            $queryData->where('subjectId', trim($request->input("subjectId")));
        }
        if (!empty($request->input("subject"))) {
            $queryData->where('subject', 'like', '%' . trim($request->input("subject")) . '%');
        }
        if (!empty($request->input("description"))) {
            $queryData->where('description', trim($request->input("description")));
        }
        $queryData = $queryData->where('deletedflag',0);
        $totalRecord = $queryData->count();

       if($serviceType != "Download"){
                $offset         = (int)$request->input("offset") ? (int)$request->input("offset") : 0;
                $limit          = (int)$request->input("limit") ? (int)$request->input("limit") : $totalRecord;
                $queryData      = $queryData->offset($offset)->limit($limit);
            }
     
        //$responseData = $queryData->offset($offset)->limit($limit)->orderBy('subject', 'ASC')->get();
        $responseData   = $queryData->orderBy('subject', 'ASC')->get();
        if($serviceType == "Download"){  
            $userId = Crypt::decryptString($request->input("userId"));
           $downloadResponse = $this->downloadSubjectList($responseData, $userId);

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
        } else {
            $i = $offset;
                foreach ($responseData as $key => $value) {
                    $responseData[$key]->slNo = ++$i;
                    $responseData[$key]->encId = Crypt::encryptString($value->subjectId);
                    $responseData[$key]->description = !empty($responseData[$key]->description) ?$responseData[$key]->description : '';
                }
                //$success = true; 
                $status     = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE'); 
               
        }
        
       }
    }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'subjectcontroller',
                'Method' => 'viewSubjectCategory',
                'Error'  => $t->getMessage()
            ]);
            $status     = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg        = config('constant.EXCEPTION_MESSAGE');               
        }
        return response()->json([
            "status" => $status,
            // "sucess" => $success,
            "statusCode" => $statusCode,
            "msg" => $msg,
            "data" => $responseData,
            "totalRecord" => $totalRecord
        ], $statusCode);
    }
    
 // Created By  :  Nitish Nanda ||  Created On  : 20-05-2022 || Service method Name :getSubjectCategory  || Description:  Get subject category details list
     public function getSubjectCategory(Request $req){
        $msg = '';
        try{
        $statusCode = config('constant.SUCCESS_CODE');
        $queryData = subjectModel::select('subjectId','subject','description','createdOn')
        ->where([['deletedFlag', 0]]);
        if (!empty($req->input("id"))) {
            $id  = Crypt::decryptString($req->input("id"));
            $queryData->where([['subjectId', $id]]);
        }
        $responseData = $queryData->get();
        
       
        if ($responseData->isEmpty()) {
            $msg = 'No record found.';
        } else {
            $response = array();
            foreach ($responseData as $res) {
                $res['encId']               = Crypt::encryptString($res->subjectId);
                $res['subject']        = $res->subject;
                $res['createdOn']               = $res->createdOn;
                array_push($response, $res);
            }
            $responseData = (count($response) > 1) ? $response : $response[0];
        }

    }
    catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'subjectcontroller',
            'Method'     => 'getSubjectCategory',
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
     // Created By  :  Nitish Nanda ||  Created On  : 20-05-2022 || Service method Name :   updateSubject || Descripttion:  update subject category details list  

     public function updateSubjectCategory(Request $request)
    {
  
        $status = "ERROR";
        DB::beginTransaction();
        try{
        $arrData = $request->all();
         $id  = Crypt::decryptString($arrData['encId']);
         if (!empty($request->all())) {
             $validator = Validator::make($arrData, [
             'subject'  => 'required|max:20|unique:subject,subject,'.$id.',subjectId,deletedFlag,0',
             'description'=>'max:300' 
             ], 
             [
                 'subject.required'  => 'subject Category Name is mandatory.',
                 'subject.max'       => 'subject Category Name length exceded Max Limit.'
                 
             ]);

            if ($validator->fails()) {
                
               
                $errors = $validator->errors();
                $msg = array();
                foreach ($errors->all() as $message) {
                    $msg[] = $message;
                }
                $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
            } else { 
                $id  = Crypt::decryptString($request->input("encId"));
              
                $dataArr['subject']      = trim($arrData['subject']);
                $dataArr['description']      = trim($arrData['description']);
                $dataArr['updatedOn']           = Carbon::now('Asia/Kolkata');
                $dataArr['updatedBy']           = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;
                 $upObj = subjectModel::where('subjectId',$id)->update($dataArr);
                if ($upObj) {
                    $status = "SUCCESS";
                    $statusCode = config('constant.SUCCESS_CODE');
                    $msg = "subject Updated successfuly";
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
            'Controller' => 'subjectcontroller',
            'Method'     => 'updateSubjectCategory',
            'Error'      => $t->getMessage()
        ]);
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
 // Created By  :  Nitish Nanda ||  Created On  : 20-05-2022 || Service method Name : deleteSubject  || Description:  delete subject category details list  
 public function deleteSubjectCategory(Request $request)
 {
    
     $status = "ERROR";
     DB::beginTransaction();
     try{
     $id = Crypt::decryptString($request->input("encId"));
     $obj = subjectModel::find($id);
     $dataArr['deletedFlag'] = 1;
     $dataArr['updatedOn']    = Carbon::now('Asia/Kolkata');
     $dataArr['updatedBy']   = (!empty($request->input("userId"))) ? Crypt::decryptString($request->input("userId")) : 0;

     $upObj = subjectModel::where('subjectId',$id)->update($dataArr);
     if ($upObj) {
         $status = "SUCCESS";
         $statusCode = config('constant.SUCCESS_CODE');
         $msg = "Subject  deleted successfully";
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
            'Controller' => 'subjectcontroller',
            'Method'     => 'deleteSubjectCategory',
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
  /* Created By : Nitish Nanda || Created On : 19-08-2022 || Service method Name : downloadSubjectList || Description: downloadSubjectList data  */
  private function downloadSubjectList($getCsvData, $userId)
  { 
      $status     = "ERROR";
      $statusCode = config('constant.EXCEPTION_CODE');
      $msg        = '';
      $responseData = '';  
      try { 
          $csvFileName = "Subject_List_".$userId."_".time().".csv";
          $csvColumns = ["Sl. No", "Subject", "Description","Created On"];

          $csvDataArr = array();
          $slno = 1;
          foreach($getCsvData as $csvData){
              $csvDataArr[] = [
                  $slno,
                  !empty($csvData->subject) ? $csvData->subject : '--',
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
              'Controller' => 'subjectcontroller',
              'Method' => 'downloadSubjectList',
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
