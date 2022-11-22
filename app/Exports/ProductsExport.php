<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
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
        $product = Product::with(['chart_of_account', 'measurement_unit', 'attributes']);

        if (array_key_exists('chart_of_accounts_id', $requestInfo)) {
            $product->where('chart_of_accounts_id', $requestInfo['chart_of_accounts_id']);
        }

        return $product->get();
    }

    public function map($product): array
    {
        $rows = [];
        if (!is_null($product->attributes)) {
            foreach ($product->attributes as $listValue) {
                $listAttributes = json_decode($listValue->attribute, true);
                if (!is_null($listAttributes)) {
                    $rows[] = [
                        'Atributo: ' . $listAttributes['title'],
                        !is_null($listValue) ? 'Valor: ' . $listValue->value : " ",
                    ];
                }
            }
        }
        return [
            $product->title,
            $product->description,
            !is_null($product->measurement_unit) ? $product->measurement_unit->title : '',
            !is_null($product->measurement_unit) ? $product->measurement_unit->unit : '',
            !is_null($product->chart_of_account) ? $product->chart_of_account->title : '',
            $rows,
            $product->created_at
        ];
    }

    public function headings(): array
    {
        return [
            'Nome do Produto',
            'Descrição',
            'Unidade de Medida',
            'Sigla',
            'Plano de Contas',
            'Atributos',
            'Data da Criação'
        ];
    }
}
