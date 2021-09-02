<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreBankRequest;
use App\Services\BankService as BankService;

class BankController extends Controller
{
    private $bankService;
    public function __construct(BankService $bankService)
    {
        $this->bankService = $bankService;
    }

    public function index()
    {
        $banks = $this->bankService->getAllBank();
        return response($banks);
    }

    public function show($id)
    {
        $bank = $this->bankService->getBank($id);
        return response($bank);
    }

    public function store(StoreBankRequest $request)
    {
        $bank = $this->bankService->postBank($request->title);
        return response($bank, 201);
    }

    public function update(StoreBankRequest $request, $id)
    {
       $bank = $this->bankService->putBank($id, $request->title);
       return response($bank);
    }

    public function destroy($id)
    {
       $bank = $this->bankService->deleteBank($id);
       return response('');
    }
}
