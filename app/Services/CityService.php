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

    public function getAllCity()
    {
        return $this->city->get();
    }

    public function getCity($id)
    {
        return $this->city->with('state')->findOrFail($id);
    }

    public function postCity($cityInfo)
    {
        $city = new City;
        return $city->create($cityInfo);
    }

    public function putCity($id, $cityInfo)
    {
        $city = $this->city->findOrFail($id);
        $city->fill($cityInfo)->save();
        return $city;
    }

    public function deleteCity($id)
    {
      $this->city->findOrFail($id)->delete();
      return true;
    }

}
