<?php

namespace App\Services;
use Spatie\Activitylog\Models\Activity;

class LogsService
{

    public function getAllLogs($requestInfo)
    {
        $orderBy = $requestInfo['orderBy'] ?? Utils::defaultOrderBy;
        $order = $requestInfo['order'] ?? Utils::defaultOrder;
        $perPage = $requestInfo['perPage'] ?? Utils::defaultPerPage;
        return Activity::orderBy($orderBy, $order)->paginate($perPage);;
    }

    public function getLogs($log_name, $subject_id, $requestInfo)
    {
        $orderBy = $requestInfo['orderBy'] ?? Utils::defaultOrderBy;
        $order = $requestInfo['order'] ?? Utils::defaultOrder;
        $perPage = $requestInfo['perPage'] ?? Utils::defaultPerPage;
        return Activity::where([
            ['log_name', $log_name],
            ['subject_id', $subject_id]
        ])->orderBy($orderBy, $order)->paginate($perPage);
    }

}

