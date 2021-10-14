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
        $orderBy = $requestInfo['orderBy'] ?? Utils::defaultOrderBy;
        $order = $requestInfo['order'] ?? Utils::defaultOrder;
        $perPage = $requestInfo['perPage'] ?? Utils::defaultPerPage;
        return $this->country->orderBy($orderBy, $order)->paginate($perPage);
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
        $state = [];
        $city = [];

        $this->country->findOrFail($id)->delete();

        $collectionStates = $this->state->where('country_id', $id)->get(['id']);
        foreach ($collectionStates as $stateID) {
            $state[] = $stateID->id;
        }

        $collectionCities = $this->city->whereIn('states_id', $state)->get(['id']);
        foreach ($collectionCities as $cityID) {
            $city[] = $cityID->id;
        }

        $this->state->destroy($state);
        $this->city->destroy($city);
        return true;
    }

}
