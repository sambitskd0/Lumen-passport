<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;


class CommonMasterController extends Controller
{
    //

    public function getAfile(Request $request, $filepath, $extension = '')
    {
        try {
            if($extension == "csv"){
                $mimetype = 'text/csv';
            }else{ 
                $mimetype = Storage::mimeType($filepath . '.' . $extension);
            }            
            
            if (in_array($mimetype, ['image/jpeg', 'image/jpeg', 'image/png', 'image/gif','application/pdf','video/mp4','audio/mpeg','audio/wav', 'text/plain', 'text/csv'])) {
                //return $mimetype;
                return Response::make(Storage::get($filepath . '.' . $extension), 200)->header('Content-Type',$mimetype);
            } else {
                return 'Invalid File type';
            }
        } catch (\Exception $e) {

            Log::error("Error", [
                'Controller' => 'CommonMasterController',
                'Method' => 'getAfile',
                'Error'  => $e->getMessage()
            ]);
            abort(500, $e->getMessage());
        }
    }
}
