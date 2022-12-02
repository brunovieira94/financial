<?php

namespace App\Exports;

use App\Models\Service;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ServicesExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{
    use Exportable;

    private $requestInfo;

    public function __construct($requestInfo)
    {
        $this->requestInfo = $requestInfo;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $requestInfo = $this->requestInfo;

        $service = Service::with(['chart_of_account']);
        if (array_key_exists('chart_of_accounts_id', $requestInfo)) {
            $service->where('chart_of_accounts_id', $requestInfo['chart_of_accounts_id']);
        }

        return $service->get();
    }

    public function map($service): array
    {
        return [
            $service->title,
            $service->description,
            !is_null($service->service_code) ? $service->service_code : '',
            !is_null($service->chart_of_account) ? $service->chart_of_account->title : '',
            $service->created_at,
        ];
    }

    public function headings(): array
    {
        return [
            'Nome do serviço',
            'Descrição',
            'Código de serviço',
            'Plano de Contas',
            'Data da Criação'
        ];
    }
}
