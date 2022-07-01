<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LogsService as LogsService;

class LogsController extends Controller
{

    private $logsService;

    public function __construct(LogsService $logsService)
    {
        $this->logsService = $logsService;
    }

    public function index(Request $request)
    {
        return $this->logsService->getAllLogs($request->all());
    }

    public function getLogs(Request $request, $log_name, $subject_id)
    {
        return $this->logsService->getLogs($log_name, $subject_id, $request->all());
    }

    public function getPaymentRequestLogs(Request $request, $id)
    {
        return $this->logsService->getPaymentRequestLogs($id, $request->all());
    }

    public function getPurchaseOrderLogs(Request $request, $id)
    {
        return $this->logsService->getPurchaseOrderLogs($id, $request->all());
    }

}
