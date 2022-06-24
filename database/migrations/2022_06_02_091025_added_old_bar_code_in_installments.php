<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestHasInstallments;

class AddedOldBarCodeInInstallments extends Migration
{
    public function up()
    {
        foreach (PaymentRequest::withoutGlobalScopes()->get() as $paymentRequest) {
            PaymentRequestHasInstallments::where('payment_request_id', $paymentRequest->id)
                ->update(
                    [
                        'billet_file' => $paymentRequest->billet_file,
                    ]
                );
        }
    }
}
