<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\AuthController;

class VerificationController extends Controller
{

    public function __construct()
    {
    }

    /* Created By : Deepti Ranjan || Created On : 09-06-2022 || Service method Name : verifyLinkPermission || Description: verify menu permission from redis cache */
    public function verifyLinkPermission(Request $request){

        $message = "Unauthorized";
        $privilege = "";
        $userRoleId = Crypt::decryptString($request->input("userRole"));
        $linkType = $request->input("pageType");
        
        if($userRoleId == 1){ //Allow all for admin
            $message = "Authorized"; 
            $privilege = config('constant.adminPrivilege');
        }else{
            $pageURL = $request->input("pageURL");         
            $redis_key  = AuthController::createRedisKey($userRoleId);
            $redis_data = json_decode(app('redis')->get($redis_key), true);

            $linkArr = explode("/",$pageURL);

            if (array_key_exists("2",$linkArr)) {

                $glLink = $linkArr[2]; //Global Link
                
                if(in_array($glLink, array_column($redis_data, 'gl_path'))){  //Check for Global Link

                    if (array_key_exists("3",$linkArr)){  //Check if primary Link exists

                        $plLink = $linkArr[3]; //Primary Link
                        $linkToCheck = $glLink."/".$plLink;
                        $glKey = array_search($glLink, array_column($redis_data, 'gl_path'));
                        
                        if(in_array($linkToCheck, array_column($redis_data[$glKey]['pl_links'], 'pl_path'))){  //Check for primary Link

                            $plKey = array_search($linkToCheck, array_column($redis_data[$glKey]['pl_links'], 'pl_path'));
 
                            if($linkType == 'TB'){ // check for tab authorization
                                $tbLink = $glLink."/".$plLink."/".$linkArr[4];

                                if(in_array($tbLink, array_column($redis_data[$glKey]['pl_links'][$plKey]['pl_tabs'], 'tb_path'))){  //Check for tab Link
                                    $tbKey = array_search($tbLink, array_column($redis_data[$glKey]['pl_links'][$plKey]['pl_tabs'], 'tb_path'));

                                    $message = "Authorized"; 
                                    $privilege = $redis_data[$glKey]['pl_links'][$plKey]['pl_tabs'][$tbKey]['tb_privilege'];
                                }
                                else
                                    $message = "Unauthorized";

                            } else if($linkType == 'BT'){ // check for button authorization
                                $btLink = $glLink."/".$plLink."/".$linkArr[4];

                                if(in_array($btLink, array_column($redis_data[$glKey]['pl_links'][$plKey]['pl_buttons'], 'bt_path'))){  //Check for button Link
                                    $btKey = array_search($btLink, array_column($redis_data[$glKey]['pl_links'][$plKey]['pl_buttons'], 'bt_path'));

                                    $message = "Authorized"; 
                                    $privilege = $redis_data[$glKey]['pl_links'][$plKey]['pl_buttons'][$btKey]['bt_privilege'];
                                }
                                else
                                    $message = "Unauthorized";                            
                            } else {
                                $message = "Authorized"; 
                                $privilege = $redis_data[$glKey]['pl_links'][$plKey]['pl_privilege'];
                            }
                        }
                        else
                            $message = "Unauthorized";
                    }
                    else
                        $message = "Authorized"; 
                }
            }
            else
            {
                $message = "Unauthorized";
            }
        }

        return response()->json([
            'message'   => $message,
            'privilege' => $privilege
        ], 200);
        
    }
    

}
