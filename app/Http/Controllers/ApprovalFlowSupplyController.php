<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreApprovalFlowSupplyRequest;
use App\Services\ApprovalFlowSupplyService as ApprovalFlowSupplyService;

class ApprovalFlowSupplyController extends Controller
{

    private $approvalFlowSupplyService;

    public function __construct(ApprovalFlowSupplyService $approvalFlowSupplyService)
    {
        $this->approvalFlowSupplyService = $approvalFlowSupplyService;
    }

    public function index(Request $request)
    {
        return $this->approvalFlowSupplyService->getAllApprovalFlowSupply($request->all());
    }

    public function store(StoreApprovalFlowSupplyRequest $request)
    {
        $approvalFlowSupply = $this->approvalFlowSupplyService->postApprovalFlowSupply($request->all());
        return response('', 201);
    }

    public function getUsers(Request $request)
    {
        return $this->approvalFlowSupplyService->getAllApprovalFlowSupplyUsers($request->all());
    }
}
