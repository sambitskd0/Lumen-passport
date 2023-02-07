<?php

/**
 * Created By  : Sambit Kumar Dalai
 * Created On  : 20-05-2022
 * Module Name : Auth Controller
 * Description : Managing all functions related to authentication.
 **/

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserModel;
use App\Models\SchoolModel;
use App\Models\TeacherModel;
use App\Models\EmployeeProfileModel;
use App\Models\DesignationModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

class AuthController extends Controller
{

    public function __construct()
    {
        date_default_timezone_set('Asia/Kolkata');
    }
    // 3) Login
    public function login(Request $request)
    {
        // return $request->username;
        $client = new Client();
        try {
            return $client->post(config('constant.PASSPORT')['login_endpoint'], [
                "form_params" => [
                    "client_secret" => config('constant.PASSPORT')['client_secret'],
                    "client_id" => config('constant.PASSPORT')['client_id'],
                    "grant_type" => "password",
                    "username" => $request->username,
                    "password" => $request->password,
                ]
            ]);
        } catch (BadResponseException $e) {
            return response()->json([
                "status" => 'error',
                'message' => $e->getMessage()
            ]);
        }




        /*      $success = false;
        try {
            $validationErrorArr = UserModel::ValidationHandler($request->all()); // validation 
            if (!sizeof($validationErrorArr)) {
                $credentials = $request->only(['userId', 'userType', 'password']);
                // ====== if both user type and user id given
                if ($credentials['userId'] && $credentials['userType']) {
                    $userData =  $this->getUserData((int)$credentials['userType'], $credentials);  // get user data based on user type
                    
                    if(!empty($userData)){
                        if ($credentials['userType'] == 1) { //teacher
                            $pass = $userData->teacherPwd;
                        }else if($credentials['userType'] == 2){ //school
                            $pass = $userData->schoolPwd;
                        }else if($credentials['userType'] == 3){ //officer
                            $pass = $userData->vchPassword;
                        }
                        // if user exist
                        if ($userData && Hash::check($credentials['password'], $userData->tempVchPassword)) {
                            $msg = "Reset Password";
                            $statusCode = config('constant.SUCCESS_CODE');
                            $resetFlag = 1;

                            if ($credentials['userType'] == 1) { //teacher
                                $pkUserId = Crypt::encryptString($userData->tId.'~'.$credentials['userType']);
                            }else if($credentials['userType'] == 2){ //school
                                $pkUserId = Crypt::encryptString($userData->schoolId.'~'.$credentials['userType']);
                            }else if($credentials['userType'] == 3){ //officer
                                $pkUserId = Crypt::encryptString($userData->intUserId.'~'.$credentials['userType']);
                            }

                            return response()->json([
                                "statusCode" => $statusCode,
                                "msg" => $msg,
                                "data" => $resetFlag,
                                "encId" => $pkUserId,
                                'success'   => true
                            ], $statusCode);
                            
                        }else if ($userData && Hash::check($credentials['password'], $pass)) {
                            // define custom claims for token
                            $customClaims = [
                                "exp" => config('auth.jwtExpire'),
                                "kid" => env('kid'),
                                "userId" => $request->input("userId"),
                                'authenticated_userid' => $request->input("userId"),
                                'client_id' => env('CLIENT_ID'),
                                'client_secret' => env('CLIENT_SECRET'),
                                'provision_key' => env('PROVISION_KEY'),
                                'grant_type' => env('GRANT_TYPE'),
                            ]; //end

                            // generate jwt token
                            $token =  Auth::claims($customClaims)->fromUser($userData);
                            if ($token) {

                                if ((int)$credentials['userType'] == 1) {
                                    // 1) Teacher Login
                                    $userProfileArr = $this->getTeacherProfileDetails($credentials, $userData);
                                    $userId =  $userData->tId;
                                } else if ((int)$credentials['userType'] == 2) {
                                    // 2) School Login
                                    $userProfileArr = $this->getSchoolProfileDetails($credentials, $userData);
                                    $userId = $userData->schoolId;
                                } else if ((int)$credentials['userType'] == 3) {
                                    // 3) Official Login
                                    $userProfileArr = $this->getOfficerProfileDetails($credentials, $userData);
                                    $userId = $userData->intUserId;
                                } else {
                                    $userProfileArr = null;
                                }

                                //get user role wise menu
                                $pemittedMenus = null;
                                if ($userProfileArr['userRoleId'] > 0) {
                                    $pemittedMenus = $this->getMenus($userId, $userProfileArr['userRoleId']);
                                }
                                $userProfile = json_encode($userProfileArr);
                                // user role end

                                return $this->respondWithToken($token, $pemittedMenus, $userProfile);
                            } else {
                                return response()->json(['msg' => 'Unauthorized'], 401);
                            }
                        } else { // if user not found
                            $msg = "Invalid userId/Password.";
                            $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                        }
                    }else{
                        $msg = "Invalid userId for selected user type";
                        $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                    }
                } else { // if user type and user id not given
                    $msg = "Invalid userId/Password.";
                    $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                }
            } else {
                // if validation unsuccessfull 
                $statusCode = config('constant.UNAUTHORIZED_USER');
                $msg = array_unique($validationErrorArr);
            }
        } catch (Exception $error) {
            $success = false;
            $msg = $error->getMessage();
            $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');

            Log::info("app.requests", [
                'request' => 'Login',
                'response' => $error->getMessage(),
                'Controller' => 'AuthController',
                'Method' => 'login'
            ]);
        }
        return response()->json([
            "success" => $success,
            "statusCode" => $statusCode,
            "msg" => $msg,
        ], $statusCode); */
    }


    /* Created By : Deepti Ranjan || Created On : 08-06-2022 || Service method Name : getMenus || Description: get user role wise menu from redis */
    public function getMenus($userId, $userRoleId)
    {
        $redis_key  = $this->createRedisKey($userRoleId);

        if (!Redis::exists($redis_key)) { //if key is not exist in redis
            $this->setRolePermissionInRedis($userRoleId, $redis_key); // set role wise menu in redis if not exists
        }

        return app('redis')->get($redis_key);
    }

    /* Created By : Deepti Ranjan || Created On : 08-06-2022 || Service method Name : createRedisKey || Description: create redis key */
    public function createRedisKey($userRoleId)
    {
        $getUserRole = DB::table('m_role')->select('vchRoleName')
            ->where('intRoleId', $userRoleId)->where('bitDeletedFlag', 0)
            ->first();
        $userRole   = $getUserRole->vchRoleName;
        $userRole   = strtolower(str_replace(' ', '_', $userRole));
        return $redis_key  = $userRole . "_permissions";
    }

    /* Created By : Deepti Ranjan || Created On : 08-06-2022 || Service method Name : setRolePermissionInRedis || Description: set role wise menu in redis */
    public function setRolePermissionInRedis($userRoleId, $redis_key)
    {

        $linkPermissionArr  = array();
        $pemittedMenus      = array();

        if ($userRoleId != 1) { //If not admin
            /*Start Get rol wise link permissions */
            $linkData = DB::table('m_admin_permissions')->select('txtPermissionValues')
                ->where('intId', $userRoleId)->where('bitDeletedFlag', 0)
                ->first();

            $txtPermissionValues = $linkData->txtPermissionValues;
            $linkPermissionArr = (array) json_decode($txtPermissionValues);
            $pemittedMenus = array_keys($linkPermissionArr);
            /*End Get rol wise link permissions */
        }

        $menuData = DB::table('m_link_master')->where('intModuleId', 1);
        if ($userRoleId != 1 and count($pemittedMenus) > 0) { //If not admin           
            $menuData = $menuData->whereIn('intLinkId', $pemittedMenus);
        }

        $menuData = $menuData->where('bitDeletedFlag', 0)->get();
        $menuDataCollection = collect(json_decode($menuData, true));
        $allGLMenus = $menuDataCollection->where("vchLinkType", "GL")->sortBy('intSerialNo'); // Get all global links

        $roleMenus = array();
        $glCount = 0;
        foreach ($allGLMenus as $glMenu) { //Loop for all Global links
            $roleMenus[$glCount]['gl_name']     = $glMenu['vchLinkName'];
            $roleMenus[$glCount]['gl_path']     = $glMenu['vchSlugName'];
            $roleMenus[$glCount]['gl_class']    = $glMenu['vchClassName'];
            $roleMenus[$glCount]['pl_links']    = array();

            $allPLMenus = $menuDataCollection->where("vchLinkType", "PL")->where("intParentMenuId", $glMenu['intLinkId'])->sortBy('intSerialNo');  // Get all primary links
            if (count($allPLMenus) > 0) {
                $plCount = 0;
                foreach ($allPLMenus as $plMenu) { //Loop for respective primary links
                    $roleMenus[$glCount]['pl_links'][$plCount]['pl_name']       = $plMenu['vchLinkName'];
                    $roleMenus[$glCount]['pl_links'][$plCount]['pl_path']       = $plMenu['vchSlugName'];

                    $pl_privilege = config('constant.adminPrivilege');
                    if ($userRoleId != 1) { //If not admin
                        $pl_id = $plMenu['intLinkId'];
                        $pl_privilege_id = $linkPermissionArr[$pl_id];
                        if ($pl_privilege_id == 4)
                            $pl_privilege = config('constant.adminPrivilege');
                        else
                            $pl_privilege = config('constant.viewPrivilege');
                    }
                    $roleMenus[$glCount]['pl_links'][$plCount]['pl_privilege']  = $pl_privilege;

                    /*starting for tabs*/
                    $roleMenus[$glCount]['pl_links'][$plCount]['pl_tabs']    = array();

                    $allTBMenus = $menuDataCollection->where("vchLinkType", "TB")->where("intParentMenuId", $plMenu['intLinkId'])->sortBy('intSerialNo');  // Get all tab links
                    if (count($allTBMenus) > 0) {
                        $tbCount = 0;
                        foreach ($allTBMenus as $tbMenu) { //Loop for respective tabs
                            $roleMenus[$glCount]['pl_links'][$plCount]['pl_tabs'][$tbCount]['tb_name']       = $tbMenu['vchLinkName'];
                            $roleMenus[$glCount]['pl_links'][$plCount]['pl_tabs'][$tbCount]['tb_path']       = $tbMenu['vchSlugName'];

                            $tb_privilege = config('constant.adminPrivilege');
                            if ($userRoleId != 1) { //If not admin
                                $tb_id = $tbMenu['intLinkId'];
                                $tb_privilege_id = $linkPermissionArr[$tb_id];
                                if ($tb_privilege_id == 4)
                                    $tb_privilege = config('constant.adminPrivilege');
                                else
                                    $tb_privilege = config('constant.viewPrivilege');
                            }
                            $roleMenus[$glCount]['pl_links'][$plCount]['pl_tabs'][$tbCount]['tb_privilege']  = $tb_privilege;

                            $tbCount++;
                        }
                    }
                    /*Ending for tabs*/

                    /*starting for buttons*/
                    $roleMenus[$glCount]['pl_links'][$plCount]['pl_buttons']    = array();

                    $allBTMenus = $menuDataCollection->where("vchLinkType", "BT")->where("intParentMenuId", $plMenu['intLinkId'])->sortBy('intSerialNo');  // Get all button links
                    if (count($allBTMenus) > 0) {
                        $btCount = 0;
                        foreach ($allBTMenus as $btMenu) { //Loop for respective tabs
                            $roleMenus[$glCount]['pl_links'][$plCount]['pl_buttons'][$btCount]['bt_name'] = $btMenu['vchLinkName'];
                            $roleMenus[$glCount]['pl_links'][$plCount]['pl_buttons'][$btCount]['bt_path'] = $btMenu['vchSlugName'];

                            $bt_privilege = config('constant.adminPrivilege');
                            if ($userRoleId != 1) { //If not admin
                                $bt_id = $btMenu['intLinkId'];
                                $bt_privilege_id = $linkPermissionArr[$bt_id];
                                if ($bt_privilege_id == 4)
                                    $bt_privilege = config('constant.adminPrivilege');
                                else
                                    $bt_privilege = config('constant.viewPrivilege');
                            }
                            $roleMenus[$glCount]['pl_links'][$plCount]['pl_buttons'][$btCount]['bt_privilege']  = $bt_privilege;

                            $btCount++;
                        }
                    }
                    /*ending for buttons*/

                    $plCount++;
                }
            }
            $glCount++;
        }

        $redisData = json_encode($roleMenus);
        app('redis')->set($redis_key, $redisData);
    }

    public function tokenTTL(Request $request)
    {
        $getUser = null;
        $userType = $request->userProfile['loginUserTypeId'];
        $credentials = $request->only(['userId']);

        if ($credentials['userId']) {
            if ((int)$userType === 1) {
                $userExists = TeacherModel::where('teacherId', $request->input("userId"))
                    ->exists();

                if ($userExists) {
                    $getUser = TeacherModel::where('teacherId', $request->input("userId"))
                        ->first();
                }
            }
            if ((int)$userType === 2) {
                $userExists = SchoolModel::where('schoolUdiseCode', $request->input("userId"))
                    ->exists();
                if ($userExists) {
                    $getUser = SchoolModel::where('schoolUdiseCode', $request->input("userId"))
                        ->first();
                }
            }
            if ((int)$userType === 3) {
                $userExists = UserModel::where('vchUserId', $request->input("userId"))
                    ->exists();
                if ($userExists) {
                    $getUser = UserModel::where('vchUserId', $request->input("userId"))
                        ->first();
                }
            }

            if ($userExists && $getUser) {
                $customClaims = [
                    "exp" => config('auth.jwtExpire'),
                    "kid" => env('kid'),
                    "userId" => $request->input("userId"),
                    'authenticated_userid' => $request->input("userId"),
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                    'provision_key' => env('PROVISION_KEY'),
                    'grant_type' => env('GRANT_TYPE'),
                ];

                if (!$token = Auth::claims($customClaims)->fromUser($getUser)) {
                    return response()->json(['message' => 'Unauthorized'], 401);
                } else {
                    return $this->respondWithToken($token);
                }
            }
        }
    }

    // 4) logout
    public function logout(Request $request)
    {

        try {
            foreach (Auth::user()->tokens as  $token) {
                $token->delete();
            }
            return response()->json([
                'success' => true,
                'message' => "Logged out successfully."
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' =>  $e->getMessage()
            ], 200);
        }

        /*     try {
            Auth::logout(true); // token blacklisted forever
        } catch (Exception $e) {
        }
        // show default response
        return response()->json([
            'message' => 'User successfully signed out',
            "success" => true,
        ]); */
    }

    public function refresh()
    {
        return "refreshed";
        return $this->respondWithToken(Auth::refresh());
    }
    public function respondWithToken($token, $pemittedMenus = "", $userProfile = "")
    {
        return response()->json([
            'token'         => $token,
            'token_type'    => 'bearer',
            'expires_in'    => config('auth.jwtExpire'),
            'success'       => true,
            'userProfile'   => $userProfile,
            'userMenus'     => $pemittedMenus
        ], 200);
    }
    /* function to mask nos keeping last four digits visible only */
    public static function makeMask($number)
    {
        if (!empty($number) && strlen($number) >= 4) {
            return str_repeat('X', strlen($number) - 4) . substr($number, -4);
        } else {
            return $number;
        }
    }

    public function getUserData($userType, $credentials)
    {
        // get user data based on user type
        if ($userType == 1) {
            // 1) Teacher Login
            $userData = TeacherModel::where('teacherId', $credentials["userId"])
                ->where('deletedFlag', 0)
                ->first();
        } else if ($userType == 2) {
            // 2) School Login
            $userData = SchoolModel::where('schoolUdiseCode', $credentials["userId"])
                ->where('deletedFlag', 0)
                ->first();
        } else if ($userType == 3) {
            // 3) Officer Login
            $userData = UserModel::where('vchUserId', $credentials["userId"])
                ->where('bitDeletedFlag', 0)
                ->first();
        } else {
            $userData = null;
        }

        return $userData;
    }


    /**
     * Created By   : Sambit Kumar Dalai 
     * Created On   : 17-08-2022
     * Description  : get teacher profile details
     **/
    public function getTeacherProfileDetails($credentials, $userData)
    {
        $userProfileArr['userRoleId']     = (int) $userData->accessRole;
        $userProfileArr['userRole']       = Crypt::encryptString($userData->accessRole);
        $userProfileArr['userName']       = $userData->teacherName;
        $userProfileArr['loginId']        = $userData->teacherId; // login id
        $userProfileArr['userId']         = Crypt::encryptString($userData->tId); // primary key
        $userProfileArr['userType']       = 'user';
        $userProfileArr['loginUserTypeId'] = $credentials['userType'];
        $userProfileArr['mobile']         = $userData && $userData->mobile ? Crypt::encryptString($userData->mobile) : '';
        $userProfileArr['mobileMask']     = $userData && $userData->mobile ?  $this->makeMask($userData->mobile) : '';
        $userProfileArr['email']          = $userData->teacherEmail;
        $userProfileArr['district']       = $userData->scDistrictId;
        $userProfileArr['block']          = $userData->scBlockId;
        $userProfileArr['cluster']        = $userData->scClusterId;
        $userProfileArr['userLevel']      = 1; // school level user
        $userProfileArr['school']         = $userData->schoolId > 0 ? Crypt::encryptString($userData->schoolId) : 0; // primary key
        /*Get School Udise Code*/
        $userProfileArr['udiseCode'] = 0;
        if ($userData->schoolId > 0) {
            $queryDataSchool = SchoolModel::select('schoolUdiseCode', 'schoolCategory')->where('schoolId', $userData->schoolId)->where('deletedFlag', 0)->first();
            $userProfileArr['udiseCode'] = $queryDataSchool && $queryDataSchool->schoolUdiseCode ? $queryDataSchool->schoolUdiseCode : '';
            $userProfileArr['schoolCategory'] = $queryDataSchool && $queryDataSchool->schoolCategory ? $queryDataSchool->schoolCategory : '';
        }

        /*Get Block Code*/
        $MASTERDB   = env('DB_DATABASE_MASTER');
        $queryData  = DB::table("$MASTERDB.annexture")->select('anxtName')->where('anxtType', 'TEACHER_TITLE')->where('anxtValue', $userData->teacherTitle)->first();

        $userProfileArr['designation']    = $queryData->anxtName;
        $userProfileArr['designationId']  = $userData->teacherTitle;

        /*Get Appointment Type*/
        $queryDataAnnexture  = DB::table("$MASTERDB.annexture")->select('anxtName')->where('anxtType', 'NATURE_OF_APPOINTMENT')->where('anxtValue', $userData->natureOfAppointmt)->first();

        $userProfileArr['natureOfAppointmt'] = $queryDataAnnexture->anxtName;
        $userProfileArr['joiningDt']         = $userData->joiningCurrentSchoolDt;
        $userProfileArr['loginUserType']     = $queryData->anxtName; // show in header
        return $userProfileArr;
    }

    /**
     * Created By   : Sambit Kumar Dalai 
     * Created On   : 17-08-2022
     * Description  : get school profile details
     **/
    public function getSchoolProfileDetails($credentials, $userData)
    {
        $userProfileArr['userRoleId']         = (int) $userData->accessRole;
        $userProfileArr['userRole']         = Crypt::encryptString($userData->accessRole);
        $userProfileArr['userName']         = $userData->schoolName;
        $userProfileArr['loginId']          = $userData->schoolUdiseCode; // login id
        $userProfileArr['userId']           = Crypt::encryptString($userData->schoolId); // primary key
        $userProfileArr['userType']         = 'user';
        $userProfileArr['loginUserType']    = "SCHOOL"; // show in header
        $userProfileArr['loginUserTypeId']  = $credentials['userType'];

        $userProfileArr['mobile']           = $userData && $userData->HMMobile ? Crypt::encryptString($userData->HMMobile) : '';
        $userProfileArr['mobileMask']       = $userData && $userData->HMMobile ?  $this->makeMask($userData->HMMobile) : '';

        $userProfileArr['email']            = $userData->schoolEmail;
        $userProfileArr['district']         = $userData->districtId;
        $userProfileArr['block']            = $userData->blockId;
        $userProfileArr['cluster']          = $userData->clusterId;
        $userProfileArr['userLevel']        = 1; // school level user
        $userProfileArr['school']           = $userData->schoolId > 0 ? Crypt::encryptString($userData->schoolId) : 0; // primary key
        $userProfileArr['udiseCode']        = $userData->schoolUdiseCode;
        $userProfileArr['schoolCategory']   = $userData->schoolCategory;
        $userProfileArr['designation']      = 'Head Master / Principal';
        $userProfileArr['designationId']    = '';

        return $userProfileArr;
    }

    /**
     * Created By   : Sambit Kumar Dalai 
     * Created On   : 17-08-2022
     * Description  : get officer profile details
     **/
    public function getOfficerProfileDetails($credentials, $userData)
    {
        $getEmployeeDetails = EmployeeProfileModel::where('intProfileId', $userData->intEmpProfileId)->first();
        $desigInfo =  DesignationModel::where("intDesignationId", $userData->intDesgnId)->first();
        $userProfileArr['userRoleId']         = (int) $userData->intAccessRole;
        $userProfileArr['userRole']         = Crypt::encryptString($userData->intAccessRole);
        $userProfileArr['userName']         = $userData->vchUserName;
        $userProfileArr['loginId']          = $userData->vchUserId;
        $userProfileArr['userId']           = Crypt::encryptString($userData->intUserId);
        $userProfileArr['userType']         = ($userData->intAccessRole == 1) ? 'admin' : 'user';
        $userProfileArr['loginUserType']    = "OFFICER"; // show in header
        $userProfileArr['loginUserTypeId']  = $credentials['userType'];
        $userProfileArr['mobile']           = $getEmployeeDetails && $getEmployeeDetails->vchMobileNo ? Crypt::encryptString($getEmployeeDetails->vchMobileNo) : '';
        $userProfileArr['mobileMask']       =  $getEmployeeDetails && $getEmployeeDetails->vchMobileNo ?  $this->makeMask($getEmployeeDetails->vchMobileNo) : '';
        $userProfileArr['email']            = $getEmployeeDetails && $getEmployeeDetails->vchEmailId ? $getEmployeeDetails->vchEmailId : '';
        $userProfileArr['district']         = $userData->intDistId;
        $userProfileArr['block']            = $userData->intBlockId;
        $userProfileArr['cluster']          = $userData->intClusterId;
        $userProfileArr['userLevel']        = $desigInfo ?  $desigInfo->intLevelId : '';
        $userProfileArr['school']           = $userData->schoolId > 0 ? Crypt::encryptString($userData->schoolId) : 0; // primary key
        /*Get School Udise Code*/
        $userProfileArr['udiseCode'] = 0;
        $userProfileArr['schoolCategory'] = 0;
        if ($userData->schoolId > 0) {
            $queryDataSchool = SchoolModel::select('schoolUdiseCode', 'schoolCategory')->where('schoolId', $userData->schoolId)->where('deletedFlag', 0)->first();
            $userProfileArr['udiseCode'] = $queryDataSchool->schoolUdiseCode;
            $userProfileArr['schoolCategory'] = $queryDataSchool->schoolCategory;
        }

        $userProfileArr['designation']      = $desigInfo &&  $desigInfo->vchDesignationName ? $desigInfo->vchDesignationName : ''; // officer designation
        $userProfileArr['designationId']    = $desigInfo && $desigInfo->intDesignationId ? $desigInfo->intDesignationId : ''; // officer designation id

        return $userProfileArr;
    }

    /**Action: generate temp password and email, By: Ayasakanta Swain, On: 29-Jul-2022**/
    public function sendPwd(Request $request)
    {
        $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
        $msg = null;
        $tempPass = '';

        if ($request->input("userId") != '') {

            $queryData = DB::table('m_user_master as le')
                ->select('le.vchUserId', 'le.intUserId', 'p.vchEmailId')
                ->leftJoin('m_employee_profile_master as p', function ($join) {
                    $join->on('le.intEmpProfileId', '=', 'p.intProfileId')->where('p.bitDeletedFlag', 0);
                })
                ->where('le.vchUserId', $request->input("userId"))
                ->where('le.tinActiveStatus', 1)
                ->where('le.intEmpProfileId', '>', 0)
                ->where('le.bitDeletedFlag', 0)->first();
            if (!empty($queryData)) {
                $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $tempPass =  substr(str_shuffle(str_repeat($pool, 5)), 0, 8);

                $dataArr['tempVchPassword'] = Hash::make($tempPass);
                $dataArr['dtmUpdatedOn']    = date("Y-m-d h:i:s");

                $upObj = DB::table('m_user_master')->where('intUserId', $queryData->intUserId)->update($dataArr);

                if ($queryData->vchEmailId != '') {
                    //Send temporary password to the user through email here 

                    $msg = 'A temportary password has been sent to registrated email, Please login through it and reset your password.' . $tempPass;
                    $statusCode = config('constant.SUCCESS_CODE');
                    return response()->json([
                        "statusCode" => $statusCode,
                        "msg" => $msg,
                        "data" => $tempPass,
                        'success'   => true
                    ], $statusCode);
                } else {
                    $msg = 'Due to Email id not available for this user, unable to send temporary password, please contact the administrator';
                    $statusCode = config('constant.SUCCESS_CODE');
                }
            } else {
                $msg = 'Invalid or Inactive Userid!, please contact the administrator';
                $statusCode = config('constant.SUCCESS_CODE');
            }
        } else {
            $msg = 'User Id is required!';
            $statusCode = config('constant.SUCCESS_CODE');
        }
        return response()->json([
            "statusCode" => $statusCode,
            "msg" => $msg,
            "data" => $tempPass,
            'success'   => false
        ], $statusCode);
    }

    /**Action: Reset password, By: Ayasakanta Swain, On: 30-Jul-2022**/
    public function resetPassword(Request $request)
    {
        $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
        $msg = null;
        $tempPass = '';
        $successFlag = 0;
        if ($request->input("userPass") != '' && $request->input("userConfPass") != '' && $request->input("userId") != '') {
            if ($request->input("userPass") == $request->input("userConfPass")) {

                $vchPassword        = Hash::make($request->input("userPass"));
                $arr                = explode('~', Crypt::decryptString($request->input("userId")));
                $userId             = $arr[0];
                $userType           = $arr[1];
                $dataArr['tempVchPassword']     = '';
                if ($userType == 1) { //teacher
                    $dataArr['teacherPwd']          = $vchPassword;
                    $dataArr['updatedOn']           = date("Y-m-d h:i:s");
                    $upObj = DB::table('esk_teacher.teacherProfile')->where('tId', $userId)
                        ->update($dataArr);
                } else if ($userType == 2) { //school
                    $dataArr['schoolPwd']       = $vchPassword;
                    $dataArr['updatedOn']       = date("Y-m-d h:i:s");
                    $upObj = DB::table('esk_school.school')->where('schoolId', $userId)
                        ->update($dataArr);
                } else if ($userType == 3) {  //officer
                    $dataArr['vchPassword']     = $vchPassword;
                    $dataArr['dtmUpdatedOn']    = date("Y-m-d h:i:s");
                    $upObj = DB::table('m_user_master')->where('intUserId', $userId)
                        ->update($dataArr);
                }

                $msg = 'Password reset successful!';
                $statusCode = config('constant.SUCCESS_CODE');
                $successFlag = 1;
            } else {
                $msg = 'Password and Confirm Password should match!';
                $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
            }
        } else {
            $msg = 'Some necessary inputs are missing!';
            $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
        }
        return response()->json([
            "statusCode" => $statusCode,
            "msg" => $msg,
            "data" => $successFlag,
            'success'   => true
        ], $statusCode);
    }




    /**Action: change password, By: Ayasakanta Swain, On: 23-Aug-2022**/
    public function changePassword(Request $request)
    {
        $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
        $msg = null;
        $tempPass = '';
        $successFlag = 0;
        if ($request->input("userPass") != '' && $request->input("userConfPass") != '' &&           $request->input("userId") != '' && $request->input("loginUserTypeId") > 0) {
            if ($request->input("userPass") == $request->input("userConfPass")) {

                $vchPassword        = Hash::make($request->input("userPass"));
                $currentPassword    = $request->input("currPass");

                $userId             = Crypt::decryptString($request->input("userId"));
                $userType           = $request->input("loginUserTypeId");

                if ($userType == 1) { //teacher
                    $currPass = DB::table('esk_teacher.teacherProfile')->where('tId', $userId)
                        ->value('teacherPwd');
                    $data = Hash::check($currentPassword, $currPass);
                    if (empty($data)) {

                        $msg = 'Current Password should Not match!';
                        $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                        return response()->json([
                            "statusCode" => $statusCode,
                            "msg" => $msg,
                            'success'   => true
                        ], $statusCode);
                    } else {
                        $dataArr['teacherPwd']          = $vchPassword;
                        $dataArr['updatedOn']           = date("Y-m-d h:i:s");
                        $upObj = DB::table('esk_teacher.teacherProfile')->where('tId', $userId)
                            ->update($dataArr);
                    }
                } else if ($userType == 2) { //school  

                    $currPass = DB::table('esk_school.school')->where('schoolId', $userId)
                        ->value('schoolPwd');
                    $data = Hash::check($currentPassword, $currPass);
                    if (empty($data)) {
                        $msg = 'Current Password should Not match!';
                        $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                        return response()->json([
                            "statusCode" => $statusCode,
                            "msg" => $msg,
                            'success'   => true
                        ], $statusCode);
                    } else {

                        $dataArr['schoolPwd']       = $vchPassword;
                        $dataArr['updatedOn']       = date("Y-m-d h:i:s");
                        $upObj = DB::table('esk_school.school')->where('schoolId', $userId)
                            ->update($dataArr);
                    }
                } else if ($userType == 3) {  //officer

                    $currPass = DB::table('m_user_master')->where('intUserId', $userId)
                        ->value('vchPassword');
                    $data = Hash::check($currentPassword, $currPass);

                    if (empty($data)) {

                        $msg = 'Current Password should Not match!';
                        $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                        return response()->json([
                            "statusCode" => $statusCode,
                            "msg" => $msg,
                            'success'   => true
                        ], $statusCode);
                    } else {

                        $dataArr['vchPassword']     = $vchPassword;
                        $dataArr['dtmUpdatedOn']    = date("Y-m-d h:i:s");
                        $upObj = DB::table('m_user_master')->where('intUserId', $userId)
                            ->update($dataArr);
                    }
                }

                $msg = 'Password changed successfully!';
                $statusCode = config('constant.SUCCESS_CODE');
                $successFlag = 1;
            } else {
                $msg = 'Password and Confirm Password should match!';
                $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
            }
        } else {
            $msg = 'Some necessary inputs are missing!';
            $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
        }
        return response()->json([
            "statusCode" => $statusCode,
            "msg" => $msg,
            "data" => $successFlag,
            'success'   => true
        ], $statusCode);
    }



    // NOTE: testing purposes
    public function testOauth(Request $request)
    {
        // return $request->username;
        $client = new Client(
            [
                // NOTE: disable ssl verification 
                \GuzzleHttp\RequestOptions::VERIFY  => false,
            ]
        );
        try {
            return $client->post(config('constant.OAUTH2')['login_endpoint'], [
                "form_params" => [
                    'verify' => false,
                    "provision_key" => config('constant.OAUTH2')['provision_key'],
                    "client_secret" => config('constant.OAUTH2')['client_secret'],
                    "client_id" => config('constant.OAUTH2')['client_id'],
                    "grant_type" => config('constant.OAUTH2')['grant_type'],
                    "authenticated_userid" => $request->authenticated_userid,
                ]
            ]);
        } catch (BadResponseException $e) {
            return response()->json([
                "status" => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
