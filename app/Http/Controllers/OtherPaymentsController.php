<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportInstallmentsPaidRequest;
use App\Http\Requests\OtherPaymentsRequest;
use App\Imports\InstallmentsPaidImport;
use App\Services\OtherPaymentsService;
use App\Jobs\InstallmentImportJob;
use App\Models\User;
use finfo;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Storage;

class OtherPaymentsController extends Controller
{
    private $otherPaymentsService;

    public function __construct(OtherPaymentsService $otherPaymentsService)
    {
        $this->otherPaymentsService = $otherPaymentsService;
    }

    public function storePayment(OtherPaymentsRequest $request)
    {
        return $this->otherPaymentsService->storePayment($request);
    }

    public function importPayments(ImportInstallmentsPaidRequest $request)
    {
        $fileOriginalName = $request->file('import_file')->getClientOriginalName();
        $fileExtension = last(explode('.', $fileOriginalName));

        if ($fileExtension != "xlsx" && $fileExtension != "csv") {
            return response()->json(['message' => 'Tipo de ficheiro não suportado para import'], 422);
        }

        $user = User::find($request->user()->id);

        if (is_null($user)) {
            return response()->json(['message' => 'Usuário do pedido de importação não encontrado no sistema!'], 404);
        }

       // $fileStoredName = $this->saveLocalFile($request, 'import_file');

        (new InstallmentsPaidImport($this->otherPaymentsService, $user, $fileOriginalName, $fileOriginalName))->import(request()->file('import_file'));

        //InstallmentImportJob::dispatch($this->otherPaymentsService, $user, $fileStoredName, $fileOriginalName);

        return response()->json(['message' => 'O ficheiro está a ser processado!'], 200);
    }

    public function approvedPaymentRequestsResolveStatus(Request $request)
    {
        $this->otherPaymentsService->checkOverPaymentRequestsStatus();
        return response()->json(['message' => 'Sucesso']);
    }

    public function saveLocalFile(Request $request, $fileName)
    {
        $dateTimeIdentifier = uniqid(date('HisYmd'));

        if ($request->hasFile($fileName) && $request->file($fileName)->isValid()) {
            $nameSplit = explode('.', $request[$fileName]->getClientOriginalName());
            $extension  = $nameSplit[count($nameSplit) - 1];
            $newName = "{$nameSplit[0]}_{$dateTimeIdentifier}.{$extension}";
           // dd('imports' . DIRECTORY_SEPARATOR . $newName);
            $archive = Storage::disk('local')->putFileAs('imports', $request->file('import_file'), $newName);

            return $newName;
        }
    }
}
