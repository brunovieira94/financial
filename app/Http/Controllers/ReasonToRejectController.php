<?php

namespace App\Http\Controllers;

use App\Imports\ReasonsToRejectImport;
use Illuminate\Http\Request;
use App\Services\ReasonToRejectService;
use App\Http\Requests\StoreReasonToRejectRequest;

class ReasonToRejectController extends Controller
{
    private $reasonToRejectService;
    public function __construct(ReasonToRejectService $reasonToRejectService)
    {
        $this->reasonToRejectService = $reasonToRejectService;
    }

    public function index(Request $request)
    {
        return $this->reasonToRejectService->getAllReasonToReject($request->all());
    }

    public function show($id)
    {
        return $this->reasonToRejectService->getReasonToReject($id);
    }

    public function store(StoreReasonToRejectRequest $request)
    {
        return $this->reasonToRejectService->postReasonToReject($request->all());
    }

    public function update(StoreReasonToRejectRequest $request, $id)
    {
        return $this->reasonToRejectService->putReasonToReject($id, $request->all());
    }

    public function destroy($id)
    {
       $bank = $this->reasonToRejectService->deleteReasonToReject($id);
       return response('');
    }
}
