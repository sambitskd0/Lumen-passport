<?php

/**
 * Created By  : Deepti Ranjan Dash
 * Created On  : 01-09-2022
 * Module Name : Redis Controller
 * Description : Set all common services in redis.
 **/

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\DistrictModel;
use App\Models\BlockModel;
use App\Models\ClusterModel;
use App\Models\AnnextureModel;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Collection;

class RedisController extends Controller
{
    /* Created By  :  Deepti Ranjan Dash ||  Created On  : 30-08-2022 || Service method Name : setDistrictListRedis || Description: Set District list in Redis  */
    public function setDistrictListRedis()
    {
        try{ 
            $queryData = DistrictModel::select('districtId', 'districtName', 'districtCode')->where('deletedflag', 0)->orderBy('districtName', 'ASC')->get();
            app('redis')->set('districtList', $queryData); 
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'RedisController',
                'Method'     => 'setDistrictListRedis',
                'Error'      => $t->getMessage()
            ]);           
        }        
    }

    /* Created By  :  Deepti Ranjan Dash ||  Created On  : 30-08-2022 || Service method Name : setBlockListRedis || Description: Set Block list in Redis  */
    public function setBlockListRedis()
    {
        try{ 
            $queryData = BlockModel::select('blockId', 'districtId', 'blockName', 'blockCode')->where('deletedflag', 0)->orderBy('blockName', 'ASC')->get();
            app('redis')->set('blockList', $queryData); 
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'RedisController',
                'Method'     => 'setBlockListRedis',
                'Error'      => $t->getMessage()
            ]);           
        }        
    }

    /* Created By  :  Deepti Ranjan Dash ||  Created On  : 01-09-2022 || Service method Name : setClusterListRedis || Description: Set Cluster list in Redis  */
    public function setClusterListRedis()
    {
        try{ 
            $queryData = ClusterModel::select('clusterId','districtId','blockId','clusterName','clusterCode')->where('deletedflag',0)->orderBy('clusterName', 'ASC')->get();
            app('redis')->set('clusterList', $queryData); 
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'RedisController',
                'Method'     => 'setClusterListRedis',
                'Error'      => $t->getMessage()
            ]);           
        }        
    }

    /* Created By  :  Deepti Ranjan Dash ||  Created On  : 01-09-2022 || Service method Name : setAnnextureRedis || Description: Set Annexture data in Redis  */
    public function setAnnextureRedis()
    {
        try{ 
            $queryData = AnnextureModel::select('anxtId', 'anxtType', 'anxtName', 'anxtValue', 'anxtFromLevel')->where('deletedflag', 0)
                        ->orderBy('anxtType', 'ASC')->orderBy('anxtValue', 'ASC')->get();
            app('redis')->set('annextureList', $queryData); 
        }
        catch (\Throwable $t) {
            Log::error("Error", [
                'Controller' => 'RedisController',
                'Method'     => 'setAnnextureRedis',
                'Error'      => $t->getMessage()
            ]);           
        }        
    }

}
