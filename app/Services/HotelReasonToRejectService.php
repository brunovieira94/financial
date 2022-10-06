<?php

namespace App\Services;
use App\Models\HotelReasonToReject;

class HotelReasonToRejectService
{
    private $hotelReasonToReject;

    public function __construct(HotelReasonToReject $hotelReasonToReject){
        $this->hotelReasonToReject = $hotelReasonToReject;
    }

    public function getAllHotelReasonToReject($requestInfo)
    {
        $hotelReasonToReject = Utils::search($this->hotelReasonToReject, $requestInfo);
        return Utils::pagination($hotelReasonToReject, $requestInfo);
    }

    public function getHotelReasonToReject($id)
    {
      return $this->hotelReasonToReject->findOrFail($id);
    }

    public function postHotelReasonToReject($hotelReasonToRejectInfo)
    {
       $hotelReasonToReject = new HotelReasonToReject();
       return $hotelReasonToReject->create($hotelReasonToRejectInfo);
    }

    public function putHotelReasonToReject($id, $hotelReasonToRejectInfo)
    {
        $hotelReasonToReject = $this->hotelReasonToReject->findOrFail($id);
        $hotelReasonToReject->fill($hotelReasonToRejectInfo)->save();
        return $hotelReasonToReject;
    }

    public function deleteHotelReasonToReject($id)
    {
      $this->hotelReasonToReject->findOrFail($id)->delete();
      return true;
    }
}
