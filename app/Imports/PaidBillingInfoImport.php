<?php

namespace App\Imports;

use App\Models\PaidBillingInfo;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;

class PaidBillingInfoImport implements ToCollection, WithValidation, WithHeadingRow, WithChunkReading, ShouldQueue
{

    use Importable;

    public function collection(Collection $rows)
    {
        foreach ($rows as $row)
        {
            $payDate = str_replace("/", "-", $row['data_de_pagamento']);
            $date = str_replace("/", "-", $row['data']);

            PaidBillingInfo::create([
                'created_at' => date('Y-m-d', strtotime($date)),
                'operator' => $row['operador'],
                'reserve' => $row['reserva'],
                'supplier_value' => floatval($row['valor_parceiro']),
                'boleto_value' => $row['valor_boleto'] ? floatval(explode("R$ ", $row['valor_boleto'])[1]) : null,
                'boleto_code' => $row['codigo_boleto'],
                'pay_date' => date('Y-m-d', strtotime($payDate)),
                'remark' => $row['observacao'],
                'oracle_protocol' => $row['protocolo_oracle'],
                'bank' => $row['banco'],
                'bank_code' => $row['codigo'],
                'agency' => $row['agencia'],
                'account' => $row['conta'],
                'form_of_payment' => $row['forma_de_pagamento'],
                'hotel_name' => $row['nome_hotel'],
                'cnpj_hotel' => $row['cnpj_cpf'],
                'payment_voucher' => $row['comprovante_transfeera'],
                'payment_method' => $row['metodo_de_pagamento'],
                'payment_bank' => $row['banco_de_pagamento'],
                'payment_remark' => $row['observacao_de_pagamento'],
                'service_id' => $row['id_servico_cangooroo'],
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'reserva' => 'required',
            'valor_parceiro' => 'required',
        ];
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

}
