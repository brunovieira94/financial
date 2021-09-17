<?php

namespace App\Services;
use Spatie\Activitylog\Models\Activity;

class LogsService
{

    public function getAllLogs($logsInfo)
    {
        $orderBy = $logsInfo['orderBy'] ?? 'created_at';
        $order = $logsInfo['order'] ?? 'desc';
        $perPage = $logsInfo['perPage'] ?? 20;
        return Activity::orderBy($orderBy, $order)->paginate($perPage);;
    }

    public function getLogs($log_name, $subject_id, $logsInfo)
    {
        $orderBy = $logsInfo['orderBy'] ?? 'created_at';
        $order = $logsInfo['order'] ?? 'desc';
        $perPage = $logsInfo['perPage'] ?? 20;
        return Activity::where([
            ['log_name', $log_name],
            ['subject_id', $subject_id]
        ])->orderBy($orderBy, $order)->paginate($perPage);
    }

}

