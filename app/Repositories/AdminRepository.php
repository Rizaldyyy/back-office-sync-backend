<?php

namespace App\Repositories;
use Request;

use App\Models\AdminLog;

/**
* Admin resource repository
*/
class AdminRepository
{
	function __construct(){
	}
	
    public static function createLog($adminId, $cluster="", $action, $detail)
    {
        $log = AdminLog::create([
            'admin_id' => $adminId,
            'cluster' => $cluster,
            'action' => $action,
            'detail' => $detail,
            'admin_ip' => request()->ip()
        ]);

        return $log;
    }

}