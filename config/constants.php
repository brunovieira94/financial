<?php

use Illuminate\Support\Facades\Config;

return [
    'status' => [
        'open' => '0',
        'approved' => '1',
        'disapproved' => '2',
        'canceled' => '3',
        'paid out' => '4',
        'error' => '5',
        'cnab generated' => '6',
        'finished' => '7',
        'multiple approval' => '8',
        'transfer approval' => '9'
    ],
    '123_secret' => 'aTShrHeIPXCuNPKLKOalzQrLxYESlodQI2Nbr2jg',
    'statusPt' => [
        'Em Processo',
        'Aprovada',
        'Reprovada',
        'Cancelada',
        'Paga',
        'Erro',
        'CNAB Gerado',
        'Finalizada',
    ],
    'billingStatus' => [
        'open' => '0',
        'approved' => '1',
        'disapproved' => '2',
        'canceled' => '3',
        'paid out' => '4',
        'finished' => '5',
    ],
    'billingStatusPt' => [
        'Em Processo',
        'Aprovada',
        'Reprovada',
        'Cancelada',
        'Paga',
        'Finalizada',
    ],
    'systemPaymentMethod' => [
        'cnab' => 0,
        'gui' => 1,
        'import' => 2,
        'update by import' => 3, // Used if an installment was updated after being paid by any of the other payment methods above.
    ],
    'systemPaymentMethodPt' => [
        'CNAB',
        'Interface Gráfica',
        'Baixa por Importação de Planilha',
        'Atualização por Importação de Planilha'
    ],
];
