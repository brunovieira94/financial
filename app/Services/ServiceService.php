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
        if (array_key_exists('search', $requestInfo)) {
            $requestInfo['search'] =  ltrim($requestInfo["search"], 0);
        }

        $service = Utils::search($this->service, $requestInfo);

        if (array_key_exists('chart_of_accounts_id', $requestInfo)) {
            $service->where('chart_of_accounts_id', $requestInfo['chart_of_accounts_id']);
        }

        return Utils::pagination($service->with('chart_of_account'), $requestInfo);
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
