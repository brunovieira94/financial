<?php

namespace App\Services;
use App\Models\City;

class CityService
{
    private $city;
    public function __construct(City $city)
    {
        $this->city = $city;
    }

    public function getAllCity($requestInfo)
    {
        $city = Utils::search($this->city,$requestInfo);
        return Utils::pagination($city->with('state'),$requestInfo);
    }

    public function getCity($id)
    {
        return $this->city->with('state')->findOrFail($id);
    }

    public function postCity($cityInfo)
    {
        $city = new City;
        $city = $city->create($cityInfo);
        return $this->city->with('state')->findOrFail($city->id);
    }

    public function putCity($id, $cityInfo)
    {
        $city = $this->city->findOrFail($id);
        $city->fill($cityInfo)->save();
        return $this->city->with('state')->findOrFail($city->id);
    }

    public function deleteCity($id)
    {
      $this->city->findOrFail($id)->delete();
      return true;
    }

}
