<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreApprovalFlowRequest;
use App\Http\Requests\PutApprovalFlowRequest;
use App\Services\ApprovalFlowService as ApprovalFlowService;

class ApprovalFlowController extends Controller
{

    private $approvalFlowService;

    public function __construct(ApprovalFlowService $approvalFlowService)
    {
        $this->approvalFlowService = $approvalFlowService;
    }

    public function index(Request $request)
    {
        return $this->approvalFlowService->getAllApprovalFlow($request->all());
    }

    public function store(StoreApprovalFlowRequest $request)
    {
        $approvalFlow = $this->approvalFlowService->postApprovalFlow($request->all());
        return response('', 201);
    }

}
