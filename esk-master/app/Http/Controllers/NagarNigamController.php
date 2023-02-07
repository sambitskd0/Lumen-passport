<?php
/**
    * Created By  : saubhagya ranjan patra 
	* Created On  : 10-05-2022
	* Module Name : manage nagar nigam
	* Description : nagarnigam add, view,delete,edit,search actions.
**/
namespace App\Http\Controllers;
use App\Models\NagarNigamModel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class NagarNigamController extends Controller
{     
    
    public function getNagarnigam(Request $request)
    {    
        $msg = '';        
        $responseData = '';
        try{
            $status = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
            $queryData = NagarNigamModel::select('nagarId','districtId','blockId','nagarType','panchayatName','panchayatCode')->where('deletedflag',0);
            if (!empty($request->input("id"))) {
                $id  = Crypt::decryptString($request->input("id"));
                $queryData->where([['nagarId', $id]]);
            }
            $queryData = $queryData->with(['district','block']); 
            $responseData = $queryData->get();
       
            if($responseData->isEmpty()){
                $msg = 'No record found.';
            }else{
                $response = array();
                foreach($responseData  as $res){
                    $res['encId']         = Crypt::encryptString($res->nagarId);
                    $res['districtName']  = $res->district->districtName;
                    $res['blockName']     = ($res->blockId>0)?$res->block->blockName:'';
                    array_push($response,$res);
                }
                $responseData = (count($response)>1)?$response:$response[0];          
            }
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller'    => 'NagarNigamController',
                'Method'        => 'getNagarnigam',
                'Error'         => $t->getMessage()
            ]);
            $status = "ERROR";
            $statusCode     = config('constant.EXCEPTION_CODE');
            $msg            = config('constant.EXCEPTION_MESSAGE');              
        }
        return response()->json([
            "status"        => $status,
            "statusCode"    => $statusCode,
            "msg"           => $msg,
            "data"          => $responseData
        ],$statusCode);
    }  

    /* Created By  :  Saubhgya Ranjan Patra ||  Created On  : 10-05-2022 || Component Name : AddmanagenagarnigamComponent || Description: Create nagarnigam */
    public function addNagarnigam(Request $req)
    {
        //return $req;
        $status = "ERROR";
        DB::beginTransaction();
        try{
            if(!empty(request()->all())) {
                $validator = Validator::make($req->all(), [
                    'nagarType'         => 'required',
                    'districtId'        => 'required',
                    'blockId'           => 'required_if:nagarType,==,2',
                    'panchayatName'     => 'required_if:nagarType,==,2|max:40|unique:nagarnigams,panchayatName,' . ',nagarId,deletedFlag,0,nagarType,'.$req->input("nagarType").',districtId,'.$req->input("districtId").',blockId,'.$req->input("blockId"),
                    'panchayatCode'     => 'required_if:nagarType,==,2|digits_between:1,15|unique:nagarnigams,panchayatCode,' . ',nagarId,blockId,'.$req->input("blockId").',deletedFlag,0',
                    'municipaltyName'   => 'required_if:nagarType,==,1|max:40|unique:nagarnigams,panchayatName,' . ',nagarId,deletedFlag,0,nagarType,'.$req->input("nagarType").',districtId,'.$req->input("districtId"),
                    'municipaltyCode'   => 'required_if:nagarType,==,1|digits_between:1,15|unique:nagarnigams,panchayatCode,' . ',nagarId,districtId,'.$req->input("districtId").',deletedFlag,0',
                ],[
                    'nagarType.required'        => 'Nagarnigam  type is mandatory.',
                    'districtId.required'       => 'District id is mandatory.',
                    'blockId.required'          => 'Block id is mandatory.',
                    'panchayatName.required'    => 'Panchayat Name is mandatory.',
                    'panchayatName.max'         => 'Panchayat name length should not be greater than 40 characters.',
                    'panchayatName.unique'      => 'The combination of district, block and panchayat name has already been taken.',
                    'panchayatCode.required'    => 'Panchayat code is mandatory.',
                    'panchayatCode.unique'      => 'The combination of block and panchayat code has already been taken.',
                    'municipaltyName.required'  => 'municipality name is mandatory.',
                    'municipaltyName.max'       => 'municipality name length should not be greater than 40 characters.',
                    'municipaltyName.unique'    => 'The combination of district, block and municipality name has already been taken.',
                    'municipaltyCode.required'  => 'Municipalty code is mandatory.',
                    'municipaltyCode.unique'    => 'The combination of district and municipalty code has already been taken.',
                ]); 
                        
                if($validator->fails()) {
                    $errors = $validator->errors();
                    $msg = array();
                    foreach ($errors->all() as $message) {
                        $msg[] = $message;
                    }
                    $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                }else{        
                    $obj = new NagarNigamModel();
                    $obj->nagarType             = $req->input("nagarType");
                    $obj->districtId            = $req->input("districtId");
                 
                    if($req->input("nagarType") == 2){
                        $obj->blockId = $req->input("blockId");
                        $obj->panchayatName = $req->input("panchayatName");
                        $obj->panchayatCode = $req->input("panchayatCode");
                    }else{
                        $obj->panchayatName = $req->input("municipaltyName");
                        $obj->panchayatCode = $req->input("municipaltyCode");
                    }
                    $obj->createdBy= (!empty($req->input("userId"))) ? Crypt::decryptString($req->input("userId")) : 0;
                    $obj->createdOn= Carbon::now('Asia/Kolkata');
                     //return $obj;
                    if($obj->save()){
                        $status = "SUCCESS";
                        $statusCode = config('constant.SUCCESS_CODE');
                        $msg = "Nagarnigam added successfuly";
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
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'NagarNigamController',
                'Method'     => 'addNagarnigam',
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
    /* Created By  :  Saubhgya Ranjan Patra ||  Created On  : 10-05-2022 || Component Name : EditmanagenagarnigamComponent || Description: update data of  nagarnigam  */
    public function updateNagarnigam(Request $req)
    {
        $status = "ERROR";
        $msg = '';
        DB::beginTransaction();
        try {
            if(!empty(request()->all())) {
                $id = Crypt::decryptString($req->input("encId")); 
                // return $req;
                $validator = Validator::make($req->all(), [
                    'encId'  => 'required',
                    'nagarType'  => 'required',
                    'districtId'  => 'required',
                    'blockId'     => 'required_if:nagarType,==,2',
                    'panchayatName' => 'required_if:nagarType,==,2|max:40|unique:nagarnigams,panchayatName,' .$id. ',nagarId,deletedFlag,0,nagarType,'.$req->input("nagarType").',districtId,'.$req->input("districtId").',blockId,'.$req->input("blockId"),
                    'panchayatCode' => 'required_if:nagarType,==,2|digits_between:1,15|unique:nagarnigams,panchayatCode,' .$id. ',nagarId,blockId,'.$req->input("blockId").',deletedFlag,0',
                    'municipaltyName'    => 'required_if:nagarType,==,1|max:40|unique:nagarnigams,panchayatName,' .$id. ',nagarId,deletedFlag,0,nagarType,'.$req->input("nagarType").',districtId,'.$req->input("districtId"),
                    'municipaltyCode'    => 'required_if:nagarType,==,1|digits_between:1,15|unique:nagarnigams,panchayatCode,' .$id. ',nagarId,districtId,'.$req->input("districtId").',deletedFlag,0',
                ],[
                    'encId.required'         => 'Nagarnigam Id is mandatory.', 
                    'nagarType.required'     => 'Nagarnigam  type is mandatory.',
                    'districtId.required'    => 'District id is mandatory.',
                    'blockId.required'       => 'Block id is mandatory.',
                    'panchayatName.required' => 'Panchayat Name is mandatory.',
                    'panchayatName.max'         => 'Panchayat name length should not be greater than 40 characters.',
                    'panchayatName.unique'      => 'The combination of district, block and panchayat name has already been taken.',
                    'panchayatCode.required'    => 'Panchayat code is mandatory.',
                    'panchayatCode.unique'      => 'The combination of block and panchayat code has already been taken.',
                    'municipaltyName.required'  => 'municipality name is mandatory.',
                    'municipaltyName.max'       => 'municipality name length should not be greater than 40 characters.',
                    'municipaltyName.unique'    => 'The combination of district and municipality name has already been taken.',
                    'municipaltyCode.required'  => 'Municipalty code is mandatory.',
                    'municipaltyCode.unique'    => 'The combination of district and municipality code has already been taken.',
                ]);
        
                if($validator->fails()) {
                    $errors = $validator->errors();
                    $msg = array();
                    foreach ($errors->all() as $message) {
                        $msg[] = $message;
                    } 
                    $statusCode = config('constant.VALIDATION_EXCEPTION_CODE');
                }else{   

                    $obj = NagarNigamModel::find($id);               
                    
                    $dataArr['nagarType']         = trim($req->input("nagarType"));
                    $dataArr['districtId']        = trim($req->input("districtId"));
                    if($req->input("nagarType") == 2){
                        $dataArr['blockId']       = trim($req->input("blockId"));
                        $dataArr['panchayatName'] = trim($req->input("panchayatName"));
                        $dataArr['panchayatCode'] = trim($req->input("panchayatCode"));
                    }else{
                        $dataArr['panchayatName'] = trim($req->input("municipaltyName"));
                        $dataArr['panchayatCode'] = trim($req->input("municipaltyCode"));
                    }
                    $dataArr['updatedOn']         = Carbon::now('Asia/Kolkata');;
                    $dataArr['updatedBy']         = (!empty($req->input("userId"))) ? Crypt::decryptString($req->input("userId")) : 0;

                    $upObj = NagarNigamModel::where('nagarId',$id)->update($dataArr);
                    if($upObj){
                        $status     = "SUCCESS";
                        $statusCode = config('constant.SUCCESS_CODE');
                        $msg        = "Nagarnigam updated successfuly";
                    }else{
                        $statusCode = config('constant.DB_EXCEPTION_CODE');
                        $msg        = "Something went wrong while updating the data.";
                    }
                }
            }else{
                $statusCode = config('constant.REQUEST_ERROR_CODE');
                $msg        = "Something went wrong, Please try later.";
            }
            DB::commit(); 
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'NagarNigamController',
                'Method' => 'updateNagarnigam',
                'Error'  => $t->getMessage()
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

    /* Created By  :  Saubhgya Ranjan Patra ||  Created On  : 09-05-2022 || Component Name : ViewmanagenagarnigamComponent || Description: single delete nagarnigam   */
    public function deleteNagarnigam(Request $req)
    {
        
        $status = "ERROR";
        DB::beginTransaction();
        try{
            $status = "SUCCESS";
            $id = Crypt::decryptString($req->input("encId"));
            
            $dataArr['deletedFlag'] = 1;
            $dataArr['updatedOn']   = Carbon::now('Asia/Kolkata');;
            $dataArr['updatedBy']   = (!empty($req->input("userId"))) ? Crypt::decryptString($req->input("userId")) : 0;

            $upObj = NagarNigamModel::where('nagarId',$id)->update($dataArr);
            if($upObj){
                $status = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');
                $msg = "Nagarnigam deleted successfuly";
                $success = true;
            }else{
                $statusCode = config('constant.DB_EXCEPTION_CODE');
                $msg = "Something went wrong while deleting the data.";
                $success = false;
            }
            DB::commit(); 
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'NagarNigamController',
                'Method'     => 'deleteNagarnigam',
                'Error'      => $t->getMessage()
            ]);
            DB::rollback();
            $status     = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg        = config('constant.EXCEPTION_MESSAGE');               
        }
        return response()->json([
            "status"     => $status,
            "statusCode" => $statusCode,
            "msg"        => $msg,
            "success" => $success
        ],$statusCode);
    }
    
    public function viewNagarnigam(Request $req)
    {     
        $msg = '';
        $responseData = '';
        try{
            $status = "SUCCESS";
            $statusCode = config('constant.SUCCESS_CODE');
            $serviceType= $req->input("serviceType");
            $queryData = NagarNigamModel::select('nagarId','districtId','blockId','nagarType','panchayatName','panchayatCode')->where('deletedflag',0);
            
            if(!empty($req->input("nagarType"))){
                $queryData->where('nagarType',$req->input("nagarType"));
            }
            if(!empty($req->input("blockId"))){
                $queryData->where('blockId',$req->input("blockId"));
            }
            if($req->input("blockId") == '0'){
                $queryData->where('blockId',0);
            }
            if(!empty($req->input("districtId"))){
                $queryData->where('districtId',$req->input("districtId"));
            }
            if(!empty($req->input("panchayatName"))){
                $queryData->where('panchayatName',trim($req->input("panchayatName")));
            }
            if(!empty($req->input("panchayatCode"))){
                $queryData->where('panchayatCode',trim($req->input("panchayatCode")));
            }
            $queryData = $queryData->where('deletedflag',0);
            $queryData = $queryData->with(['district','block']); 
            $totalRecord = $queryData->count();
            if($serviceType != "Download"){
                $offset         = (int)$req->input("offset") ? (int)$req->input("offset") : 0;
                $limit          = (int)$req->input("limit") ? (int)$req->input("limit") : $totalRecord;
                $queryData      = $queryData->offset($offset)->limit($limit);
            }
            //$responseData = $queryData->offset($offset)->limit($limit)->orderBy('panchayatName', 'ASC')->get();            
            $responseData   = $queryData->orderBy('panchayatName', 'ASC')->get();
            if($serviceType == "Download"){  
                $userId = Crypt::decryptString($req->input("userId"));
               $downloadResponse = $this->downloadNagarNigamList($responseData, $userId);
   
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
            if($responseData->isEmpty()){
                $msg = 'No record found.';
               
            }else{
                $i = $offset;
                foreach ($responseData as $key => $value) {
                    $responseData[$key]->slNo = ++$i;
                    $responseData[$key]->encId = Crypt::encryptString($value->nagarId);
                    $responseData[$key]->districtName = !empty($value->district->districtName)?$value->district->districtName :'';
                    $responseData[$key]->blockName = !empty($value->block->blockName)?$value->block->blockName :'';
                }
                $status     = "SUCCESS";
                $statusCode = config('constant.SUCCESS_CODE');      
            }
        }
    }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'NagarNigamController',
                'Method' => 'viewNagarnigam',
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
    /* Created By : Nitish Nanda || Created On : 19-08-2022 || Service method Name : downloadNagarNigamList || Description: downloadNagarNigamList data  */
    private function downloadNagarNigamList($getCsvData, $userId)
    { 
        $status     = "ERROR";
        $statusCode = config('constant.EXCEPTION_CODE');
        $msg        = '';
        $responseData = '';  
        try { 
            $csvFileName = "NagarNigam_List_".$userId."_".time().".csv";
            $csvColumns = ["Sl. No", "District","Block","Type", "Municipality/ Panchayat Name
            ","Municipality/ Panchayat Code
            "];

            $csvDataArr = array();
            $slno = 1;
            foreach($getCsvData as $csvData){
                $csvDataArr[] = [
                    $slno,
                    !empty($csvData->district->districtName) ? $csvData->district->districtName : '--',                    
                    !empty($csvData->block->blockName) ? $csvData->block->blockName : '--',                    
                   // !empty($csvData->nagarType == 2) ? 'Panchayat' : (($csvData->nagarType == 1)? 'Municipality': '--'),
                    (!empty($csvData->nagarType) && ($csvData->nagarType == 1)) ? 'Municipality' :  'Panchayat',                    
                    !empty($csvData->panchayatName) ? $csvData->panchayatName : '--',                    
                    !empty($csvData->panchayatCode) ? $csvData->panchayatCode : '--',                    
                   
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
                'Controller' => 'NagarNigamController',
                'Method' => 'downloadNagarNigamList',
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
