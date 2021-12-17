<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

use App\Models\BankAccount;

class CheckCNABItauWallet implements Rule
{
    public function __construct()
    {

    }

    public function passes($attribute, $value)
    {
        $bankAccountCompany = BankAccount::findOrFail($value);
        if(!$bankAccountCompany->wallet)
            return false;

        return true;
    }

    public function message()
    {
        return 'A carteira não foi informada';
    }
}
