<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\PaymentRequest;

use function PHPUnit\Framework\isNull;

class DuplicatePaymentRequest implements Rule
{
    private $business_id;
    private $force_registration;
    private $paymentRequestID;

    public function __construct($business_id, $force_registration, $paymentRequestID = null)
    {
        $this->business_id = $business_id;
        $this->force_registration = $force_registration;
        $this->paymentRequestID = $paymentRequestID;
    }

    public function passes($attribute, $value)
    {
        if (is_null($this->business_id)) {
            $paymentRequest = PaymentRequest::with('business')
                ->findOrFail($this->paymentRequestID);
            $this->business_id = $paymentRequest->business->id;
        }

        if (!is_null($this->paymentRequestID)) {
            $paymentRequest = PaymentRequest::with('business')
                ->findOrFail($this->paymentRequestID);

            $columnValidation = '';
            if ($paymentRequest->bar_code == null) {
                $columnValidation = $paymentRequest->invoice_number;
            } else {
                $columnValidation = $paymentRequest->bar_code;
            }
            if ($columnValidation == $value) {
                return true;
            }
        }

        if (PaymentRequest::with('business')
            ->where($attribute, $value)
            ->whereRelation('business', 'id', '=', $this->business_id)
            ->exists()
        ) {
            response('Já existe a nota fiscal ou boleto cadastrado para esse negócio!', 422)->send();
            die();
        }

        if (PaymentRequest::where($attribute, $value)
            ->exists()
        ) {
            if ($this->force_registration) {
                return true;
            }
            response('Já existe a nota fiscal ou boleto cadastrado no sistema!', 424)->send();
            die();
        }
        return true;
    }

    public function message()
    {
        return 'Informação duplicada é necessário enviar o parâmetro force_registration como true';
    }
}
