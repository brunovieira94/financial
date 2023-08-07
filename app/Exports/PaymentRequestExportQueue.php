<?php

namespace App\Exports;

use App\Exports\Utils as ExportsUtils;
use App\Models\PaymentRequestClean;
use App\Services\Utils;
use Vitorccs\LaravelCsv\Concerns\Exportable;
use Vitorccs\LaravelCsv\Concerns\FromQuery;
use Vitorccs\LaravelCsv\Concerns\WithHeadings;
use Vitorccs\LaravelCsv\Concerns\WithMapping;

class PaymentRequestExportQueue implements FromQuery, WithMapping, WithHeadings
{
    private $paymentRequest;
    private $arrayFilterStatus;
    private $baseFilterPaymentRequest;
    private $requestInfo;
    private $paymentRequestDeleted;
    private $duePaymentRequestReport;

    public function __construct(PaymentRequestClean $paymentRequest, $arrayFilterStatus = [], $requestInfo, $paymentRequestDeleted = false, $duePaymentRequestReport = false)
    {
        $this->paymentRequest = $paymentRequest;
        $this->arrayFilterStatus = $arrayFilterStatus;
        $this->requestInfo = $requestInfo;
        $this->paymentRequestDeleted = $paymentRequestDeleted;
        $this->duePaymentRequestReport = $duePaymentRequestReport;
    }

    use Exportable;

    public function query()
    {
        $requestInfo = $this->requestInfo;
        $arrayFilterStatus = $this->arrayFilterStatus;
        $paymentRequest = $this->paymentRequest::query();
        $paymentRequest = $paymentRequest->with(ExportsUtils::withModelDefaultExport('payment-request'));
        $paymentRequest = Utils::baseFilterReportsPaymentRequest($paymentRequest, $requestInfo);
        $paymentRequest = $paymentRequest->whereHas('installments', function ($query) use ($requestInfo) {
            if (array_key_exists('from', $requestInfo)) {
                $query = $query->where('extension_date', '>=', $requestInfo['from']);
            }
            if (array_key_exists('to', $requestInfo)) {
                $query = $query->where('extension_date', '<=', $requestInfo['to']);
            }
            if ($this->duePaymentRequestReport) {
                if (!array_key_exists('to', $requestInfo) && !array_key_exists('from', $requestInfo)) {
                    $query = $query->whereBetween('extension_date', [now(), now()->addMonths(1)]);
                }
            }
        });

        $paymentRequest = $paymentRequest->whereHas('approval', function ($query) use ($arrayFilterStatus) {
            if (!empty($arrayFilterStatus)) {
                $query = $query->whereIn('status', $arrayFilterStatus);
            }
        });

        if ($this->paymentRequestDeleted) {
            $paymentRequest = $paymentRequest->withTrashed();
        }

        return $paymentRequest;
    }

    public function map($paymentRequest): array
    {
        return ExportsUtils::exportPaymentRequestData($paymentRequest);
    }

    public function headings(): array
    {
        return ExportsUtils::exportPaymentRequestColumn();
    }
}
