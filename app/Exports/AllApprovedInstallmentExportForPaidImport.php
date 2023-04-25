<?php

namespace App\Exports;

use App\Models\PaymentRequestHasInstallmentsClean;
use App\Services\Utils;
use Config;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AllApprovedInstallmentExportForPaidImport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{
    use Exportable;

    private $requestInfo;

    public function __construct($requestInfo)
    {
        $this->requestInfo = $requestInfo;
    }

    public function collection()
    {
        $requestInfo = $this->requestInfo;

        if (!array_key_exists('company', $requestInfo))
            return collect([]);

        $installment = PaymentRequestHasInstallmentsClean::with(['payment_request', 'payment_request.company', 'group_payment', 'bank_account_provider']);

        $installment = $installment
            ->whereHas('payment_request', function ($query) use ($requestInfo) {
                $query->whereHas('approval', fn ($query) => $query->where('status', 1));
                $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo, true);
            })
            ->where('status', '<>', Config::get('constants.status.paid out'))
            ->where('status', '<>', Config::get('constants.status.cnab generated'));

        $installment = Utils::baseFilterReportsInstallment($installment, $requestInfo);

        return $installment->get();
    }

    public function map($installment): array
    {
        return [
            $installment->payment_request->company->company_name,
            '', // Nm Banco
            '', // Cod Banco
            '', // Agencia
            '', // Conta Bancaria
            $installment->payment_request_id,
            $installment->parcel_number,
            '', // Data
            '', // Valor Pago
            ''  // Forma de Pagamento
        ];
    }

    public function headings(): array
    {
        return [
            'Empresa',
            'Nome do Banco',
            'Código do Banco',
            'Agência',
            'Conta Bancária',
            'Conta',
            'Parcela',
            'Data do Pagamento',
            'Valor Pago',
            'Forma de Pagamento'
        ];
    }
}
