<?php

namespace App\Services;
use App\Models\Country;
use App\Models\City;
use App\Models\State;

class CountryService
{
    private $country;
    private $city;
    private $state;

    public function __construct(Country $country, City $city, State $state)
    {
        $this->country = $country;
        $this->city = $city;
        $this->state = $state;
    }

    public function getAllCountry($requestInfo)
    {
        $country = Utils::search($this->country,$requestInfo);
        return Utils::pagination($country,$requestInfo);
    }

    public function getCountry($id)
    {
      return $this->country->findOrFail($id);
    }

    public function postCountry($countryInfo)
    {
        $country = new Country;
        return $country->create($countryInfo);
    }

    public function putCountry($id, $countryInfo)
    {
        $country = $this->country->findOrFail($id);
        $country->fill($countryInfo)->save();
        return $country;
    }

    public function deleteCountry($id)
    {
        $this->country->findOrFail($id)->delete();
        return true;
    }
}
