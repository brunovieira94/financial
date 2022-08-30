<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreApprovalFlowRequest;
use App\Models\PaymentRequest;
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

    public function show($id)
    {
        return $this->approvalFlowService->getApprovalFlowById($id);
    }

    public function update(Request $request, $id)
    {
        return $this->approvalFlowService->putApprovalFlow($id, $request->all());
    }

    public function destroy($id)
    {
        if(PaymentRequest::withoutGlobalScopes()->where('group_approval_flow_id', $id)->exists())
        {
            return response()->json([
                'error' => 'Existe solicitação de pagamento neste fluxo de aprovação'
            ], 422);
        }
        $this->approvalFlowService->deleteApprovalFlow($id);
        return response('');
    }
}
