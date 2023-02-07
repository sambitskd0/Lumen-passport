<?php

/**
 * Created By  : saubhagya ranjan patra
 * Created On  : 04-05-2022
 * Module Name : District Master Controller
 * Description : managing all district master for add, view,delete,edit,search actions.
 * Modified By: Swagatika Sahoo
 * Modified On: 12-05-2022
 * Modified By: Deepti Ranjan Dash
 * Modified On: 01-09-2022
 * Modified For: Redis implementation
 **/

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\RedisController;
use App\Models\AnnextureModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Collection;


class AnnextureController extends Controller
{
    /* Created By  :  Saubhgya Ranjan Patra ||  Created On  : 04-05-2022 || Service method Name : viewDistrict || Description: District Listing and Search   */
    public function getAnnexture(Request $req)
    {
        $status     = "SUCCESS";
        $statusCode = config('constant.SUCCESS_CODE');
        $msg        = '';
        $responseData   = '';
        try {
            if(!app('redis')->exists('annextureList')){
                RedisController::setAnnextureRedis(); // Set Cluster List in redis if not exists                
            } 

            $annextureList = app('redis')->get('annextureList');            
            $responseData = collect(json_decode($annextureList,true));  

            if (!empty($req->input("anxtId"))) {
                $responseData = $responseData->where('anxtId', trim($req->input("anxtId")));
            }
            
            if (!empty($req->input("anxtType"))) {
                $responseData = $responseData->where('anxtType', trim($req->input("anxtType")));
            }
            
            if (!empty($req->input("anxtName"))) {
                $responseData = $responseData->where('anxtName', trim($req->input("anxtName")));
            }            

            if (!empty($req->input("anxtValue"))) {
                $responseData = $responseData->where('anxtValue', trim($req->input("anxtValue")));
            }
            
            if (!empty($req->input("anxtFromLevel")) && ((int)$req->input("anxtFromLevel") > 0)) {
                $responseData = $responseData->where('anxtValue', '>', $req->input("anxtFromLevel"));
            }
            
            if ($responseData->isEmpty()) {
                $msg = 'No record found.';
            } else {
                $response = array();
                foreach ($responseData  as $res) {
                    $res['encId'] = Crypt::encryptString($res['anxtId']);
                    array_push($response, $res);
                }
                $responseData = $response;
            }
        } catch (\Throwable $t) {
            Log::error("Error", [
                'Controller'=> 'AnnextureController',
                'Method'    => 'getAnnexture',
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
            "data"      => $responseData
        ],$statusCode);
    }

    /* Created By  :  Sambit Kumar Dalai ||  Created On  : 14-06-2022 || Service method Name : commonservice || Description: common function for getting all type of annexture details   */
    public function getCommonAnnexture(Request $req)
    {
        $status     = "SUCCESS";
        $statusCode = config('constant.SUCCESS_CODE');
        $msg        = '';
        $annexureData   = ''; 
        try {
            if(!app('redis')->exists('annextureList')){
                RedisController::setAnnextureRedis(); // Set Cluster List in redis if not exists                
            } 

            $annextureList = app('redis')->get('annextureList');            
            $responseData = collect(json_decode($annextureList,true));  
            
            $annexureData = [];
            foreach ($req['anxtTypes'] as $value) {
                $annexureData[$value] = array_values($responseData->where('anxtType', trim($value))->toArray());
            }
        } catch (\Throwable $t) {
            Log::error("Error", [
                'Controller'=> 'AnnextureController',
                'Method'    => 'getCommonAnnexture',
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
            "data"      => $annexureData
        ],$statusCode);
    }
}
