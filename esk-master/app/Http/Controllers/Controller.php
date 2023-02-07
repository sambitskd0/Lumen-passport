<?php

namespace App\Http\Controllers;


use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;


class Controller extends BaseController
{
    //
    /* Created By : Nitish Nanda || Created On : 22-08-2022  || Service method Name : downloadCsv || Description: Create and save the CSV file */
    protected function downloadCsv($csvFileName, $csvColumns, $csvDataArr)
    {  
        $status     = "ERROR";
        $statusCode = config('constant.EXCEPTION_CODE');
        $msg        = '';
        $responseData = '';  
        try {            
            $csvFile = tmpfile();
            $csvPath = stream_get_meta_data($csvFile)['uri'];

            $fp = fopen($csvPath, 'w');
            fputcsv($fp, $csvColumns);
            foreach ($csvDataArr as $csvData) {           
                fputcsv($fp, $csvData);        
            }
            fclose($fp);

            $storageFolder  = Storage::disk('local');
            $downloadPath   = 'downloads/csv/';
            if($storageFolder->putFileAs($downloadPath, $csvPath, $csvFileName)){
                $status       = "SUCCESS";
                $statusCode   = config('constant.SUCCESS_CODE'); 
                $responseData = $downloadPath.$csvFileName;  
            }
        } catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'Controller',
                'Method'     => 'downloadCsv',
                'Error'      => $t->getMessage()
            ]);
            $status     = "ERROR";
            $statusCode = config('constant.EXCEPTION_CODE');
            $msg        = config('constant.EXCEPTION_MESSAGE');           
        }
        
        return [
            "status"     => $status,
            "statusCode" => $statusCode,
            "msg"        => $msg,
            "data"       => $responseData
        ];        
    }
}
