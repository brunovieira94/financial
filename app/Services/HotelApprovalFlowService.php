<?php

namespace App\Services;

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

    public function postHotelApprovalFlow($hotelApprovalFlowInfo)
    {
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
