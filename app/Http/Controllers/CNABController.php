<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PostCNAB240Request;
use App\Services\CNABService as CNABService;

class CNABController extends Controller
{

    private $cnabService;
    public function __construct(CNABService $cnabService)
    {
        $this->cnabService = $cnabService;
    }

    public function shipping(Request $request)
    {
        return $this->cnabService->generateCNAB240Shipping($request->all());
    }

    public function return(Request $request) {

        $returnFile = $request->file('return-file');

        $processArchive = new \Eduardokum\LaravelBoleto\Cnab\Retorno\Cnab240\Banco\Itau($returnFile);
        $processArchive->processar();


        $teste = $processArchive->getDetalhes();
        dd($teste[1]);
        return 'teste';
    }

}
