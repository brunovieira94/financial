<?php

use App\Models\PaymentRequest;
use App\Models\PaymentRequestHasInstallments;
use App\Models\PaymentRequestHasTax;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterValueInstallments extends Migration
{
    public function up()
    {
        foreach(PaymentRequest::whereNotNull('net_value')
        ->where('net_value', '>', '0')
        ->get() as $paymentRequest)
        {
            $paymentHasInstallments = PaymentRequestHasInstallments::where('payment_request_id', $paymentRequest->id)->get();
            $quantityInstallments = PaymentRequestHasInstallments::where('payment_request_id', $paymentRequest->id)->max('parcel_number');

            if($quantityInstallments > 0){
            $valueParcel = $paymentRequest->net_value / $quantityInstallments;
            $necessaryRoundUP = false;
            $necessaryRoundDown = false;

            $whole = floor($valueParcel);
            $fraction = $valueParcel - $whole;

            if(strlen($fraction) > 4)
            {
                switch(substr($valueParcel, -1)) {
                    case('3');
                    $necessaryRoundUP = true;
                    break;

                    case('7'):
                    $necessaryRoundDown = true;
                    break;

                    default:
                    $necessaryRoundUP = true;
                }
            }

            foreach($paymentHasInstallments as $key => $installment)
            {
                $paymentRequestHasInstallments = PaymentRequestHasInstallments::findOrFail($installment->id);
                $paymentRequestHasInstallments->initial_value = $valueParcel;

                if($quantityInstallments == $paymentRequestHasInstallments->parcel_number)
                {
                    if($necessaryRoundUP)
                    {
                        $paymentRequestHasInstallments->initial_value += 0.01;
                    }
                    if($necessaryRoundDown)
                    {
                        $paymentRequestHasInstallments->initial_value -= 0.01;
                    }
                }
                $paymentRequestHasInstallments->portion_amount = $paymentRequestHasInstallments->initial_value + $paymentRequestHasInstallments->fess - $paymentRequestHasInstallments->discount;
                $paymentRequestHasInstallments->portion_amount = str_replace(',', '.', number_format($paymentRequestHasInstallments->portion_amount, 2, ',', ''));
                $paymentRequestHasInstallments->save();
            }
            }

        }
    }
}
