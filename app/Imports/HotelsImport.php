<?php

namespace App\Imports;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Hotel;
use App\Models\HotelHasBankAccounts;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Collection;

class HotelsImport implements ToCollection, WithValidation, WithHeadingRow
{

    use Importable;

    private $isValid = false;
    private $formOfPayment = null;
    private $billingType = null;
    private $accountType = null;

    public function collection(Collection $rows)
    {
        foreach ($rows as $row)
        {
            switch (strtolower($row['tipo_de_faturamento'])) {
                case "faturado":
                    $this->billingType = 0;
                    break;
                case "pré-pagamento":
                    $this->billingType = 1;
                    break;
                case "vcn":
                    $this->billingType = 2;
                    break;
                default:
                    $this->billingType = null;
            }

            switch (strtolower($row['validacao_cnpj'])) {
                case "ok":
                    $this->isValid = 1;
                    break;
                case "not ok":
                    $this->isValid = 0;
                    break;
                default:
                    $this->isValid = 0;
            }

            switch (strtolower($row['forma_de_pagamento'])) {
                case "corrente":
                case "pix":
                    $this->formOfPayment = 1;
                    break;
                case "boleto":
                    $this->formOfPayment = 0;
                    break;
                default:
                    $this->formOfPayment = null;
            }

            $hotel = Hotel::create([
                'id_hotel_cangooroo'=> $row['id_hotel_cangooroo'],
                'id_hotel_omnibees'=> $row['id_hotel_ominibees'],
                'hotel_name'=> $row['royalty'],
                'chain'=> $row['rede'],
                'email'=> $row['e_mail_respondido'],
                'email_omnibees'=> $row['e_mail_cadastro_ominibees'],
                'phone'=> $row['telefone'],
                'billing_type'=> $this->billingType,
                'form_of_payment'=> $this->formOfPayment,
                'holder_full_name'=> $row['nome_completo_do_titular'],
                'cpf_cnpj'=> $row['cpfcnpj'],
                'cnpj_hotel'=> $row['cnpj_omnibees'],
                'observations'=> $row['observacoes'],
                'is_valid'=> $this->isValid,
            ]);

            if($row['banco'] && $row['codigo_banco'] && $row['agencia'] && $row['conta'] && $row['tipo_de_conta']){
                switch (strtolower($row['tipo_de_conta'])) {
                    case "poupança":
                        $this->accountType = 0;
                        break;
                    case "corrente":
                        $this->accountType = 1;
                        break;
                    case "salário":
                        $this->accountType = 2;
                        break;
                    default:
                        $this->accountType = 1;
                }

                $agencyNumber = explode("-", $row['agencia']);
                $accountNumber = explode("-", $row['conta']);

                $bank = Bank::where('bank_code', $row['codigo_banco'])->first('id');

                if($bank)
                {
                    $bankAccount = BankAccount::create([
                        'agency_number'=> $agencyNumber[0],
                        'agency_check_number'=> array_key_exists(1, $agencyNumber) ? $agencyNumber[1] : null,
                        'account_number'=> $accountNumber[0],
                        'account_check_number'=> array_key_exists(1, $accountNumber) ? $accountNumber[1] : null,
                        'bank_id'=> $bank->id,
                        'account_type'=> $this->accountType,
                    ]);

                    HotelHasBankAccounts::create([
                        'hotel_id'=> $hotel->id,
                        'bank_account_id'=> $bankAccount->id,
                        'default_bank'=> 1,
                    ]);
                }
            }
        }
    }

    public function rules(): array
    {
        return [
            'id_hotel_cangooroo' => 'required|unique|max:150',
            'id_hotel_ominibees' => 'max:150',
            'royalty' => 'required|max:150',
            'rede' => 'max:150',
            'e_mail_respondido' => 'required|max:150',
            'e_mail_cadastro_ominibees' => 'max:150',
            'telefone' => 'max:150',
            'nome_completo_do_titular' => 'max:150',
            'cpfcnpj' => 'required|max:150',
        ];
    }

}
