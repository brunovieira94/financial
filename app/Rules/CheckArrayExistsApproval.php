<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

use App\Models\BankAccount;
use App\Models\PaymentRequest;
use App\Models\User;
use Faker\Provider\ar_EG\Payment;

class CheckArrayExistsApproval implements Rule
{
    public function __construct()
    {
    }

    public function passes($attribute, $value)
    {
        switch ($attribute) {
            case 'payment_requests':
                foreach ($value as $id) {
                    if (PaymentRequest::withoutGlobalScopes()->where('id', $id)->exists()) {
                        return true;
                        break;
                    }
                }
                break;
            case 'users':
                foreach ($value as $id) {
                    if (User::where('id', $id)->exists()) {
                        return true;
                        break;
                    }
                }
                break;
        }
    }

    public function message()
    {
        return 'Usuário ou solicitação de pagamento não encontrada no banco de dados.';
    }
}
