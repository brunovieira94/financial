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
            $provider->cpf,
            $provider->full_name,
            $provider->rg,
            $provider->surname,
            $provider->birth_date,
            $provider->accept_billet_payment,
            $provider->credit_card_payment,
            $provider->international,
        ];
    }

    public function headings(): array
    {
        return [
            'Razão Social',
            'Nome fantasia',
            'Nome Fornecedor na 123',
            'CNPJ',
            'Usuario 123 E mail',
            'Responsável Fornecedor',
            'Email Responsável Fornecedor',
            'Telefone Responsável Fornecedor',
            'Inscrição Estadual',
            'Inscrição Municipal',
            'Plano de Contas',
            'Centro de Custos',
            'Categoria do fornecedor',
            'CEP',
            'Cidade',
            'Endereço',
            'Email',
            'Número',
            'Complemento',
            'Distrito',
            'Telefones',
            'Tipo de Fornecedor',
            'CPF',
            'Nome Completo do Fornecedor',
            'RG',
            'Apelido',
            'Data de Nascimento',
            'Aceitar pagamento por boleto?',
            'Pagamento via cartão de crédito',
            'Fornecedor Internacional?'
        ];
    }
}
