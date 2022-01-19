<?php

namespace App\Services;

use App\Models\LogActivity;

class LogsService
{

    public function getAllLogs($requestInfo)
    {
        $orderBy = $requestInfo['orderBy'] ?? Utils::defaultOrderBy;
        $order = $requestInfo['order'] ?? Utils::defaultOrder;
        $perPage = $requestInfo['perPage'] ?? Utils::defaultPerPage;
        return LogActivity::orderBy($orderBy, $order)->paginate($perPage);;
    }

    public function getLogs($log_name, $subject_id, $requestInfo)
    {
        return LogActivity::where([
            ['log_name', $log_name],
            ['subject_id', $subject_id]
        ])->get();
    }
}
