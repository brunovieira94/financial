<?php

namespace App\Imports;

use App\Models\PaidBillingInfo;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Collection;
use Carbon\Carbon;


class DailyPaidBillingInfoImport implements ToCollection, WithValidation, WithHeadingRow
{
    public $not_imported = 0;
    public $imported = 0;

    use Importable;

    public function collection(Collection $rows)
    {
        foreach ($rows as $row)
        {
            $date = str_replace("/", "-", $row['date_time']);

            if($row['response_msg'] == 'Aprovado')
            {
                PaidBillingInfo::create([
                    'created_at' => $row['date_time'] ? date('Y-m-d H:i', strtotime($date)) : Carbon::now(),
                    'operator' => 'Bank3',
                    'reserve' => $row['codigo_tratado'],
                    'supplier_value' => floatval($row['amount']),
                    'pay_date' => Carbon::now(),
                    'remark' => 'Extraído do relatório Bank3',
                    'hotel_name' => $row['merchant_name'],
                    'payment_voucher' => 'Cartão Utilizado',
                    'payment_method' => 'VCN',
                    'payment_bank' => 'Cartão Utilizado',
                    'payment_remark' => 'Cartão Utilizado',
                ]);
                $this->imported++;
            }
            else
            {
                $this->not_imported++;
            }
        }
    }

    public function rules(): array
    {
        return [
            'codigo_tratado' => 'required|max:150',
            'amount' => 'required',
            'merchant_name' => 'max:150',
        ];
    }

}
