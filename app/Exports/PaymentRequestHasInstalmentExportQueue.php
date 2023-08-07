<?php

namespace App\Exports;

use App\Exports\Utils as ExportsUtils;
use App\Models\PaymentRequestHasInstallmentsClean;
use App\Services\Utils;
use Vitorccs\LaravelCsv\Concerns\Exportable;
use Vitorccs\LaravelCsv\Concerns\FromQuery;
use Vitorccs\LaravelCsv\Concerns\WithHeadings;
use Vitorccs\LaravelCsv\Concerns\WithMapping;


class PaymentRequestHasInstalmentExportQueue implements FromQuery, WithMapping, WithHeadings
{
    private $paymentRequestInstallment;
    private $arrayFilterStatus;
    private $requestInfo;
    private $paymentRequestDeleted;
    private $duePaymentRequestReport;

    public function __construct(PaymentRequestHasInstallmentsClean $paymentRequestInstallment, $arrayFilterStatus = [], $requestInfo, $paymentRequestDeleted = false, $duePaymentRequestReport = false)
    {
        $this->paymentRequestInstallment = $paymentRequestInstallment;
        $this->arrayFilterStatus = $arrayFilterStatus;
        $this->requestInfo = $requestInfo;
        $this->paymentRequestDeleted = $paymentRequestDeleted;
        $this->duePaymentRequestReport = $duePaymentRequestReport;
    }

    use Exportable;

    public function query()
    {
        $requestInfo = $this->requestInfo;
        $installment = $this->paymentRequestInstallment::query();
        $installment = $installment->with(ExportsUtils::withModelDefaultExport('payment-request-installments'));
        $installment->whereHas('payment_request', function ($query) use ($requestInfo) {
            $query->whereHas('approval', function ($query) use ($requestInfo) {
                if (!empty($arrayFilterStatus)) {
                    $query = $query->whereIn('status', $arrayFilterStatus);
                }
            });
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo, true);
        });
        if (array_key_exists('from', $requestInfo)) {
            $installment = $installment->where('extension_date', '>=', $requestInfo['from']);
        }
        if (array_key_exists('to', $requestInfo)) {
            $installment = $installment->where('extension_date', '<=', $requestInfo['to']);
        }
        if ($this->duePaymentRequestReport) {
            if (!array_key_exists('to', $requestInfo) && !array_key_exists('from', $requestInfo)) {
                $installment = $installment->whereBetween('extension_date', [now(), now()->addMonths(1)]);
            }
        }
        $installment = Utils::baseFilterReportsInstallment($installment, $requestInfo);

        return $installment;
    }

    public function map($installment): array
    {
        return ExportsUtils::exportInstallmentData($installment);
    }

    public function headings(): array
    {
        return ExportsUtils::exportInstallmentColumn();
    }
}
