<?php

namespace App\Services;
use App\Models\ReasonToReject;

class ReasonToRejectService
{
    private $reasonToReject;

    public function __construct(ReasonToReject $reasonToReject){
        $this->reasonToReject = $reasonToReject;
    }

    public function getAllReasonToReject($requestInfo)
    {
        $reasonToReject = Utils::search($this->reasonToReject, $requestInfo);
        return Utils::pagination($reasonToReject, $requestInfo);
    }

    public function getReasonToReject($id)
    {
      return $this->reasonToReject->findOrFail($id);
    }

    public function postReasonToReject($reasonToRejectInfo)
    {
       $reasonToReject = new ReasonToReject();
       return $reasonToReject->create($reasonToRejectInfo);
    }

    public function putReasonToReject($id, $reasonToRejectInfo)
    {
        $reasonToReject = $this->reasonToReject->findOrFail($id);
        $reasonToReject->fill($reasonToRejectInfo)->save();
        return $reasonToReject;
    }

    public function deleteReasonToReject($id)
    {
      $this->reasonToReject->findOrFail($id)->delete();
      return true;
    }
}
