<?php

/**
 * Created By  : Nitish Nanda
 * Created On  : 27-05-2022
 * Module Name : Master Module
 * Description : Manage GeoFencing update.
 **/

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GeoFencingModel;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class GeoFencingController extends Controller
{
    //
    /* Created By  :  Nitish Nanda ||  Created On  : 27-05-2022 || Component Name :geoFencing   || Description:  Get GeoFencingData  */
    public function getGeoFencing(Request $req)
    {
      $msg = '';
      try{
        $statusCode = config('constant.SUCCESS_CODE');
        $responseData = GeoFencingModel::select('anxtId', 'anxtType', 'anxtValue', 'createdOn')
        ->where('anxtType', 'Geo_Fencing_Distance')->where('deletedFlag', 0)->get();
    //    if ($responseData->isEmpty()) {
    //         $msg = 'No record found.';
    //     } 
    if (!empty($req->input("id"))) {
        $id  = Crypt::decryptString($req->input("id"));
        $responseData->where([['anxtId', $id]]);
    }
        else {
            $response = array();
            foreach ($responseData as $res) {
                $res['encId']               = Crypt::encryptString($res->anxtId);
                $res['anxtValue']        = $res->anxtValue;
                $res['createdOn']               = $res->createdOn;
                array_push($response, $res);
            }
            $responseData = (count($response) > 1) ? $response : $response[0];
        }
        
    }
    catch (\Throwable $t) {
        Log::error("Error", [
            'Controller' => 'GeoFencingController',
            'Method'     => 'getGeoFencing',
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


     /* Created By  :  Nitish Nanda ||  Created On  : 27-05-2022 || Component Name :geoFencing  || Description:  update GeoFencingData  */
    public function updateGeoFencing(Request $request)
    {

     $status = "ERROR";
     DB::beginTransaction();
     try{
        $arrData = $request->all();
       if (!empty($request->all())) {
            $validator = Validator::make(
                $arrData,
                 [
                    'GeoFencingValue'  => 'required|numeric',
                ],
                [
                    'GeoFencingValue.required'  => 'GeoFencingValue is mandatory.',
                    'GeoFencingValue.numeric'  => 'GeoFencingValue allow only Integer Value.',
                ]
            );
         if ($validator->fails()) {
                $errors = $validator->errors();
                $msg = array();
                foreach ($errors->all() as $message) {
                    $msg[] = $message;
                }
                $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
            } else {
                
                $id  = $request->input("encId");
                $dataArr['anxtValue']   = trim($arrData['GeoFencingValue']);
                $dataArr['updatedOn']   = date('Y-m-d h:i:s');
                $annexType      = 'Geo_Fencing_Distance';
                try {
                    $upObj = GeoFencingModel::where('anxtType', $annexType)->where('anxtId', '<>', 0)->update($dataArr);
                    $status = "SUCCESS";
                    $statusCode = config('constant.SUCCESS_CODE');
                    $msg = "GeoFencing Value Updated successfuly";
                } catch (\Illuminate\Database\QueryException $e) {
                    // Do whatever you need if the query failed to execute
                    //Write In Error Log
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
            'Controller' => 'GeoFencingController',
            'Method'     => 'updateGeoFencing',
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
}
