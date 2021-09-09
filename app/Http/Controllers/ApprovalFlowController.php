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

    public function index()
    {
        return $this->approvalFlowService->getAllApprovalFlow();
    }

    public function show($id)
    {
        return $this->approvalFlowService->getApprovalFlow($id);
    }

    public function store(StoreApprovalFlowRequest $request)
    {
        $approvalFlow = $this->approvalFlowService->postApprovalFlow($request->all());
        return response($approvalFlow, 201);
    }

    public function update(PutApprovalFlowRequest $request, $id)
    {
        return $this->approvalFlowService->putApprovalFlow($id, $request->all());
    }

    public function destroy($id)
    {
        $approvalFlow = $this->approvalFlowService->deleteApprovalFlow($id);
        return response('');
    }
}