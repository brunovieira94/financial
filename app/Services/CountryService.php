<?php

namespace App\Services;

use App\Models\Country;

class CountryService
{
    private $country;

    public function __construct(Country $country)
    {
        $this->country = $country;
    }

    public function getAllCountry($requestInfo)
    {
        $country = Utils::search($this->country, $requestInfo);
        return Utils::pagination($country, $requestInfo);
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
