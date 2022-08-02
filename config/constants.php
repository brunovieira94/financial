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
    ],
    'statusPt' => [
        'Em Processo',
        'Aprovada',
        'Reprovada',
        'Cancelada',
        'Paga',
        'Erro',
        'CNAB Gerado',
        'Finalizada',
    ]
];
