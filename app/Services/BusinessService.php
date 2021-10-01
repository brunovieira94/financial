<?php

namespace App\Services;
use App\Models\Business;
use App\Models\CostCenter;
use App\Models\User;
use App\Models\BusinessHasCostCenters;

class BusinessService
{
    private $business;
    private $user;
    private $costCenter;
    private $businessHasCostCenters;

    public function __construct(Business $business, CostCenter $costCenter, User $user, BusinessHasCostCenters $businessHasCostCenters)
    {
        $this->costCenter = $costCenter;
        $this->business = $business;
        $this->user = $user;
        $this->businessHasCostCenters = $businessHasCostCenters;
    }

    public function getAllBusiness()
    {
        $orderBy = $requestInfo['orderBy'] ?? Utils::defaultOrderBy;
        $order = $requestInfo['order'] ?? Utils::defaultOrder;
        $perPage = $requestInfo['perPage'] ?? Utils::defaultPerPage;
        return $this->business->with('user')->with('costCenter')->orderBy($orderBy, $order)->paginate($perPage);
    }

    public function getBusiness($id)
    {
      return $this->business->with('user')->with('costCenter')->findOrFail($id);
    }

    public function postBusiness($businessInfo)
    {
        $business = new Business;
        $business = $business->create($businessInfo);

        self::syncCostUser($business, $businessInfo);
        return $this->business->with('user')->with('costCenter')->findOrFail($business->id);
    }

    public function putBusiness($id, $businessInfo)
    {
        $business = $this->business->findOrFail($id);
        $business->fill($businessInfo)->save();
        self::putCostUser($id, $businessInfo);
        return $this->business->with('user')->with('costCenter')->findOrFail($business->id);
    }

    public function deleteBusiness($id)
    {
      $this->business->findOrFail($id)->delete();
      return true;
    }

    public function syncCostUser($business, $businessInfo){
        if(array_key_exists('cost_user', $businessInfo)){
            foreach($businessInfo['cost_user'] as $cost_user){
                $businessHasCostCenters = new BusinessHasCostCenters;
                $businessHasCostCenters = $businessHasCostCenters->create([
                    'cost_center_id' => $cost_user['cost_center_id'],
                    'user_id' => $cost_user['user_id'],
                    'business_id' => $business->id,
                ]);
            }
        }
    }

    public function putCostUser($id, $businessInfo){

        $updateCostUser = [];
        $createdCostUser = [];

        if(array_key_exists('cost_user', $businessInfo)){
            foreach($businessInfo['cost_user'] as $cost_user){
                if (array_key_exists('id', $cost_user)){
                    $businessHasCostCenters = $this->businessHasCostCenters->findOrFail($cost_user['id']);
                    $businessHasCostCenters->fill($cost_user)->save();
                    $updateCostUser[] = $cost_user['id'];
                } else {
                    $businessHasCostCenters = $this->businessHasCostCenters->create([
                        'cost_center_id' => $cost_user['cost_center_id'],
                        'user_id' => $cost_user['user_id'],
                        'business_id' => $id,
                    ]);
                    $createdCostUser[] = $businessHasCostCenters->id;
                }
            }

            $collection = $this->businessHasCostCenters->where('business_id', $id)->whereNotIn('id', $updateCostUser)->whereNotIn('id', $createdCostUser)->get(['id']);
            $this->businessHasCostCenters->destroy($collection->toArray());
        }
    }

}
