<?php

namespace App\Exports;

use App\Models\Provider;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProvidersExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{

    use Exportable;

    public function collection()
    {
        return Provider::with(['bank_account', 'provider_category', 'user', 'chart_of_account', 'cost_center', 'city'])->get();
    }

    public function map($provider): array
    {
        return [
            $provider->company_name,
            $provider->trade_name,
            $provider->alias,
            $provider->cnpj,
            $provider->user ? $provider->user->email : $provider->user,
            $provider->responsible,
            $provider->responsible_email,
            $provider->responsible_phone,
            $provider->state_subscription,
            $provider->city_subscription,
            $provider->chart_of_account ? $provider->chart_of_account->title : $provider->chart_of_account,
            $provider->cost_center ? $provider->cost_center->title : $provider->cost_center,
            $provider->provider_category ? $provider->provider_category->title : $provider->provider_category,
            $provider->cep,
            $provider->city ? $provider->city->title : $provider->city,
            $provider->address,
            $provider->email,
            $provider->number,
            $provider->complement,
            $provider->district,
            $provider->phones,
            $provider->provider_type,
            $provider->full_name,
            $provider->cpf,
            $provider->rg,
            $provider->birth_date,
            $provider->accept_billet_payment,
            $provider->credit_card_payment,
            $provider->international,
        ];
    }

    public function headings(): array
    {
        return [
            'Nome do Fornecedor',
            'Nome fantasia',
            'Apelido',
            'CNPJ',
            'Usuario Interno Responsável',
            'Representante do Fornecedor',
            'Email do Representante',
            'Telefone do Representante',
            'Inscrição Estadual',
            'Inscrição Municipal',
            'Plano de Contas',
            'Centro de Custos',
            'Categoria do fornecedor',
            'CEP',
            'Cidade',
            'Endereço',
            'Email da empresa',
            'Número',
            'Complemento',
            'Bairro',
            'Telefones',
            'Tipo de Pessoa',
            'Nome Completo',
            'CPF',
            'RG',
            'Data de Nascimento',
            'Aceitar pagamento por boleto?',
            'Pagamento via cartão de crédito',
            'Fornecedor Internacional?'
        ];
    }
}
