<?php

namespace App\Exports;

use App\Models\PaymentRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AllDuePaymentRequestExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{

    use Exportable;

    private $parent;

    public function collection()
    {
        return PaymentRequest::get();
    }

    public function map($paymentRequest): array
    {
        // $this->parent = CostCenter::where('id', $costCenter->parent)->first();
        // return [
        //     $costCenter->title,
        //     $costCenter->code,
        //     $this->parent ? $this->parent->code : $costCenter->parent,
        // ];

        return [
            $paymentRequest->title,
            $paymentRequest->code,
        ];
    }

    public function headings(): array
    {
        return [
            'Título',
            'Código',
            'Código do pai',
        ];
    }
}
