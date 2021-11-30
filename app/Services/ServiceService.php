<?php

namespace App\Services;
use App\Models\Service;

class ServiceService
{
    private $service;
    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function getAllService($requestInfo)
    {
        $service = Utils::search($this->service,$requestInfo);
        return Utils::pagination($service->with('chart_of_account'),$requestInfo);
    }

    public function getService($id)
    {
      return $this->service->with('chart_of_account')->findOrFail($id);
    }

    public function postService($serviceInfo)
    {
        $service = new Service;
        $service = $service->create($serviceInfo);
        return $this->service->with('chart_of_account')->findOrFail($service->id);
    }

    public function putService($id, $serviceInfo)
    {
        $service = $this->service->findOrFail($id);
        $service->fill($serviceInfo)->save();
        return $this->service->with('chart_of_account')->findOrFail($service->id);
    }

    public function deleteService($id)
    {
      $this->service->findOrFail($id)->delete();
      return true;
    }

}
