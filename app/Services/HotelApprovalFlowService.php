<?php

namespace App\Services;

use App\Models\Billing;
use App\Models\HotelApprovalFlow;

class HotelApprovalFlowService
{
    private $with = ['role'];
    private $hotelApprovalFlow;
    public function __construct(HotelApprovalFlow $hotelApprovalFlow)
    {
        $this->hotelApprovalFlow = $hotelApprovalFlow;
    }

    public function getAllHotelApprovalFlow()
    {
        return $this->hotelApprovalFlow->with($this->with)->get();
    }

    public function getHotelApprovalRoles()
    {
        $roles = [];
        $hotelApprovalFlow = $this->hotelApprovalFlow->with($this->with)->get();
        foreach ($hotelApprovalFlow as $hotelApproval) {
            if(!array_key_exists($hotelApproval->order, $roles)){
                $data = [
                    'order' => $hotelApproval->order,
                    'role_id' => $hotelApproval->role_id,
                    'title' => $hotelApproval->role->title,
                ];
                array_push($roles, $data);
            }
        }
        return $roles;
    }

    public function postHotelApprovalFlow($hotelApprovalFlowInfo)
    {
        if((count($hotelApprovalFlowInfo['order']) - 1) < $this->hotelApprovalFlow->max('order'))
        {
            Billing::whereIn('approval_status', [0,2])->where('order', '>', (count($hotelApprovalFlowInfo['order']) - 1))->update(['order' => 1]);
        }
        HotelApprovalFlow::truncate();
        $hotelApprovalFlow = new HotelApprovalFlow;
        $info = [];
        foreach ($hotelApprovalFlowInfo['order'] as $key => $roles) {
            $info['order'] = $key;
            foreach ($roles as $role) {
                $info['role_id'] = $role;
                $hotelApprovalFlow->create($info);
            }
        }
        return true;
    }
}
