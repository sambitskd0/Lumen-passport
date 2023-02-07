<?php

/**
 * Created By  : Deepti Ranjan Dash
 * Created On  : 01-09-2022
 * Module Name : Common Service Controller
 * Description : managing all common services.
 **/

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\RedisController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Collection;

class CommonServiceController extends Controller
{
    /* Created By  :  Deepti Ranjan Dash ||  Created On  : 01-09-2022 || Service method Name : getDistrict || Description: District view according to filter  */
    public function getDistrict(Request $request)
    {
        $status     = "SUCCESS";
        $statusCode = config('constant.SUCCESS_CODE');
        $msg        = '';
        $responseData   = ''; 
        try{
            if(!app('redis')->exists('districtList')){
                RedisController::setDistrictListRedis(); // set District List in redis if not exists                
            } 

            $districtList = app('redis')->get('districtList');
            $responseData = collect(json_decode($districtList,true));

            if ($responseData->isEmpty()) {
                $msg = 'No record found.';
            } else {
                $response = array();
                foreach ($responseData  as $res) {
                    $res['encId']   = Crypt::encryptString($res['districtId']);
                    array_push($response, $res);
                }
                $responseData = $response;
            }
        }

        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'CommonServiceController',
                'Method'     => 'getDistrict',
                'Error'      => $t->getMessage()
            ]);
            $status     = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg        = config('constant.EXCEPTION_MESSAGE');              
        }

        return response()->json([
            "status"        => $status,
            "statusCode"    => $statusCode,
            "msg"           => $msg,
            "data"          => $responseData
        ], $statusCode);
    }

    /* Created By  :  Deepti Ranjan Dash ||  Created On  : 01-09-2022  || Service Method Name : getAllBlock || Description: View Block list in search filter  */
    public function getBlock(Request $request)
    {
        $status     = "SUCCESS";
        $statusCode = config('constant.SUCCESS_CODE');
        $msg        = '';
        $responseData   = '';    
        try {
            if(!app('redis')->exists('blockList')){
                RedisController::setBlockListRedis(); // set Block List in redis if not exists                
            } 

            $blockList = app('redis')->get('blockList');
            $responseData = collect(json_decode($blockList,true));            
   
            if (!empty($request->input("districtId"))) {
                $responseData = $responseData->where('districtId', trim($request->input("districtId")));
            }    

            if ($responseData->isEmpty()) {
                $msg = 'No record found.';
            } else {
                $response = array();
                foreach ($responseData as $res) {
                    $res['encId'] = Crypt::encryptString($res['blockId']);
                    array_push($response, $res);
                }
                $responseData = $response;
            }
        } catch (\Throwable $t) {
            Log::error("Error", [
                'Controller'=> 'CommonServiceController',
                'Method'    => 'getBlock',
                'Error'     => $t->getMessage(),
            ]);
            $status     = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg        = config('constant.EXCEPTION_MESSAGE');
        }

        return response()->json([
            "status"    => $status,
            "statusCode"=> $statusCode,
            "msg"       => $msg,
            "data"      => $responseData,
        ], $statusCode);
    }

    /* Created By  : Deepti Ranjan Dash ||  Created On  : 01-09-2022 || Service Method Name : getCluster || Description:Get Cluster data in search filter  */
    public function getCluster(Request $request)
    {         
        $status     = "SUCCESS";
        $statusCode = config('constant.SUCCESS_CODE');
        $msg        = '';
        $responseData   = '';    
        try {
            if(!app('redis')->exists('clusterList')){
                RedisController::setClusterListRedis(); // Set Cluster List in redis if not exists                
            } 

            $clusterList = app('redis')->get('clusterList');
            $responseData = collect(json_decode($clusterList,true));  

            if (!empty($request->input("blockId"))) {
                $responseData = $responseData->where('blockId', trim($request->input("blockId")));
            }   

            if($responseData->isEmpty()){
                $msg = 'No record found.';
            }else{
                $response = array();
                foreach($responseData  as $res){
                    $res['encId'] = Crypt::encryptString($res['clusterId']);
                    array_push($response, $res);
                }
                $responseData = $response;    
            }
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'CommonServiceController',
                'Method'     => 'getCluster',
                'Error'      => $t->getMessage()
            ]);
            $status     = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg        = config('constant.EXCEPTION_MESSAGE');              
        }
        return response()->json([
            "status"    => $status,
            "statusCode"=> $statusCode,
            "msg"       => $msg,
            "data"      => $responseData
        ],$statusCode);
    }
}
