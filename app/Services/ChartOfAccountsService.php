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

    public function getAllChartOfAccounts($requestInfo)
    {
        // $orderBy = $requestInfo['orderBy'] ?? Utils::defaultOrderBy;
        // $order = $requestInfo['order'] ?? Utils::defaultOrder;
        // $perPage = $requestInfo['perPage'] ?? Utils::defaultPerPage;
        $chartOfAccounts = Utils::search($this->chartOfAccounts,$requestInfo);
        $charts = Utils::pagination($chartOfAccounts->where('parent', null),$requestInfo);
        //$charts =  $chartOfAccounts->where('parent', null)->orderBy($orderBy, $order)->paginate($perPage);
        //$charts = $this->chartOfAccounts->where('parent', null)->orderBy($orderBy, $order)->paginate($perPage);
        $nestable = $this->chartOfAccounts->nestable($charts);
        return $nestable;
    }

    public function getChartOfAccounts($id)
    {
        $chartOfAccounts = $this->chartOfAccounts->findOrFail($id)->where('id', $id)->get();
        $nestable = $this->chartOfAccounts->nestable($chartOfAccounts);
        return $nestable;
    }

    public function postChartOfAccounts($chartOfAccountsInfo)
    {
        $chartOfAccounts = new ChartOfAccounts;
        if(array_key_exists('parent', $chartOfAccountsInfo) && is_numeric($chartOfAccountsInfo['parent'])){
            $this->chartOfAccounts->findOrFail($chartOfAccountsInfo['parent'])->get();
        }
        return $chartOfAccounts->create($chartOfAccountsInfo);
    }

    public function putChartOfAccounts($id, $chartOfAccountsInfo)
    {
        $chartOfAccounts = $this->chartOfAccounts->findOrFail($id);
        if(array_key_exists('parent', $chartOfAccountsInfo)){
            if(is_numeric($chartOfAccountsInfo['parent'])){
                $this->chartOfAccounts->findOrFail($chartOfAccountsInfo['parent'])->get();
            }
            if($chartOfAccountsInfo['parent'] == $id){
                abort(500);
            }
        }
        $chartOfAccounts->fill($chartOfAccountsInfo)->save();
        return $chartOfAccounts;
    }

    public function deleteChartOfAccounts($id)
    {
        $chartOfAccounts = $this->chartOfAccounts->findOrFail($id)->where('id', $id)->get();
        $nestable = $this->chartOfAccounts->nestable($chartOfAccounts)->toArray();
        $arrayIds = Utils::getDeleteKeys($nestable);
        $this->chartOfAccounts->destroy($arrayIds);
        return true;
    }
}

