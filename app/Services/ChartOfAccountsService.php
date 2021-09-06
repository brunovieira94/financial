<?php

namespace App\Services;
use App\Models\ChartOfAccounts;

class ChartOfAccountsService
{

    private $chartOfAccounts;
    public function __construct(ChartOfAccounts $chartOfAccounts)
    {
        $this->chartOfAccounts = $chartOfAccounts;
    }

    public function getAllChartOfAccounts()
    {
        return $this->chartOfAccounts->get();
    }

    public function getChartOfAccounts($id)
    {
        return $this->chartOfAccounts->findOrFail($id);
    }

    public function postChartOfAccounts($chartOfAccountsInfo)
    {
        $chartOfAccounts = new ChartOfAccounts;
        return $chartOfAccounts->create($chartOfAccountsInfo);
    }

    public function putChartOfAccounts($id, $chartOfAccountsInfo)
    {
        $chartOfAccounts = $this->chartOfAccounts->findOrFail($id);
        $chartOfAccounts->fill($chartOfAccountsInfo)->save();
        return $chartOfAccounts;
    }

    public function deleteChartOfAccounts($id)
    {
        $this->chartOfAccounts->findOrFail($id)->delete();
        return true;
    }

}

