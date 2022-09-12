<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequestHasTax;
use App\Models\TemporaryLogUploadPaymentRequest;
use App\Models\TypeOfTax;
use Aws\S3\ObjectUploader;
use Aws\S3\S3Client;
use DB;
use Exception;
use Illuminate\Http\Request;
use Storage;

class InfoController extends Controller
{
    public function duplicateInformationSystem(Request $request)
    {
        $resume = DB::select("SELECT provider_id, invoice_number, count(*) total FROM payment_requests where invoice_number is not null AND deleted_at IS NULL group by provider_id, invoice_number having count(*) > 1;");
        $details = DB::select("SELECT id, provider_id, invoice_number, created_at, user_id, cost_center_id, amount
                FROM payment_requests
                where concat(provider_id, '-', invoice_number) IN (
                SELECT concat(provider_id, '-', invoice_number)
                FROM payment_requests where invoice_number is not null AND deleted_at IS NULL group by provider_id, invoice_number having count(*) > 1 );");

        $cpfDuplicate = DB::select("SELECT cpf, count(*) total FROM providers where cpf is not null AND deleted_at IS NULL group by cpf having count(*) > 1;");
        $cnpjDuplicate = DB::select("SELECT cnpj, count(*) total FROM providers where cnpj is not null AND deleted_at IS NULL group by cnpj having count(*) > 1;");
        $taxDuplicate = DB::select("SELECT * FROM type_of_tax;");

        return response()->json([
            'resumo' => $resume,
            'detalhes' => $details,
            'cnpj' => $cnpjDuplicate,
            'cpf' => $cpfDuplicate,
            'tax' => $taxDuplicate
        ], 200);
    }

    public function temporaryLogUploadPaymentRequest(Request $request)
    {
        return TemporaryLogUploadPaymentRequest::orderBy('id', 'desc')->limit(10)->get();
    }

    public function storageUpload(Request $request)
    {
        $nameFiles = [];
        $archives = $request->archive;
        $folder = 'teste';

        if (!is_array($archives)) {
            $archives = [
                $archives
            ];
        }

        foreach ($archives as $archive) {
            $generatedName = null;
            $data = uniqid(date('HisYmd'));
            if (is_array($archive)) {
                $archive = $archive['attachment'];
            }
            $originalName  = explode('.', $archive->getClientOriginalName());
            $extension = $originalName[count($originalName) - 1];
            $generatedName = "{$originalName[0]}_{$data}.{$extension}";
            //$upload = $archive->storeAs($folder, $generatedName);
            $s3Client = new S3Client([
                'region' => env('AWS_DEFAULT_REGION'),
                'version' => '2006-03-01'
            ]);
            $bucket = env('AWS_BUCKET');
            $key = $folder . '/' . $generatedName;
            try {
                // Using stream instead of file path
                $source = fopen($archive, 'rb');
                $uploader = new ObjectUploader(
                    $s3Client,
                    $bucket,
                    $key,
                    $source
                );
                $uploader->upload();
                array_push($nameFiles, $generatedName);
            } catch (Exception $e) {
                TemporaryLogUploadPaymentRequest::create([
                    'error' => $e->getMessage(),
                    'folder' => $folder
                ]);
                error_log($e->getMessage());
            }
        }
        return Storage::disk('s3')->temporaryUrl("teste/{$nameFiles[0]}", now()->addMinutes(30));
    }

    public function taxDelete(Request $request)
    {
        PaymentRequestHasTax::whereIn('type_of_tax_id', [9])->update(['type_of_tax_id' => 2]);
        PaymentRequestHasTax::whereIn('type_of_tax_id', [10])->update(['type_of_tax_id' => 3]);
        PaymentRequestHasTax::whereIn('type_of_tax_id', [12])->update(['type_of_tax_id' => 5]);
        PaymentRequestHasTax::whereIn('type_of_tax_id', [14])->update(['type_of_tax_id' => 7]);
        TypeOfTax::destroy([9,10,12,14]);
        PaymentRequestHasTax::whereIn('type_of_tax_id', [11,4])->update(['type_of_tax_id' => 15]);
        TypeOfTax::destroy([11,4]);

        return response()->json([
            'sucess' => 'taxas deletadas e atualizadas'
        ], 200);
    }
}
