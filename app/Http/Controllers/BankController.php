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
<<<<<<< HEAD
        return $this->bankService->getAllBank();
=======
        $banks = $this->bankService->getAllBank();
        return response($banks);
>>>>>>> b63e0ebf4354fa82953f2598b3c6263b11850d84
    }

    public function show($id)
    {
<<<<<<< HEAD
        return $this->bankService->getBank($id);
=======
        $bank = $this->bankService->getBank($id);
        return response($bank);
>>>>>>> b63e0ebf4354fa82953f2598b3c6263b11850d84
    }

    public function store(StoreBankRequest $request)
    {
<<<<<<< HEAD
        return $this->bankService->postBank($request->all());
=======
        $bank = $this->bankService->postBank($request->title);
        return response($bank, 201);
>>>>>>> b63e0ebf4354fa82953f2598b3c6263b11850d84
    }

    public function update(StoreBankRequest $request, $id)
    {
<<<<<<< HEAD
        return $this->bankService->putBank($id, $request->all());
=======
       $bank = $this->bankService->putBank($id, $request->title);
       return response($bank);
>>>>>>> b63e0ebf4354fa82953f2598b3c6263b11850d84
    }

    public function destroy($id)
    {
       $bank = $this->bankService->deleteBank($id);
       return response('');
    }
}
