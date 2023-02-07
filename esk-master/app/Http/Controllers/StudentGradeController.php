<?php

/**
 * Created By  : Nitish Nanda
 * Created On  : 16-06-2022
 * Module Name : Master Module
 * Description : studentgrade view
 **/

namespace App\Http\Controllers;
use App\Models\StudentGradeModel;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;



class StudentGradeController extends Controller
{
    //
    public function viewStudentGradeMaster(Request $request){
        $msg = '';
        try{
        $statusCode = config('constant.SUCCESS_CODE');
        $queryData = StudentGradeModel::select('studentGradeId','gradeName','minRange','maxRange','createdOn')->where([['deletedflag',0]]);
         $responseData = $queryData->get(); 
        if($responseData->isEmpty()){
            $msg = 'No record found.';
        }else{
            $response = array();
            foreach($responseData  as $res){
                $res['encId']       = Crypt::encryptString($res->studentGradeId);
               $res['gradeName']     = $res->gradeName;
               $res['minRange']     = $res->minRange;
               $res['maxRange']     = $res->maxRange;
              $res['createdOn']   = $res->createdOn;
                array_push($response,$res);
            }
        }
    }
    catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'StudentGradeController',
            'Method' => 'viewStudentGradeMaster',
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
}
