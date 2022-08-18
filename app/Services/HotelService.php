<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\BankAccount;
use App\Models\HotelHasBankAccounts;

class HotelService
{
    private $hotel;
    private $bankAccount;
    private $hotelHasBankAccounts;
    private $with = ['bank_account'];
    public function __construct(Hotel $hotel, BankAccount $bankAccount, HotelHasBankAccounts $hotelHasBankAccounts)
    {
        $this->hotel = $hotel;
        $this->bankAccount = $bankAccount;
        $this->hotelHasBankAccounts = $hotelHasBankAccounts;
    }

    public function getAllHotel($requestInfo)
    {
        $hotel = Utils::search($this->hotel, $requestInfo);
        return Utils::pagination($hotel->with($this->with), $requestInfo);
    }

    public function getHotel($id)
    {
        return $this->hotel->with($this->with)->findOrFail($id);
    }

    public function postHotel($hotelInfo)
    {
        $hotel = Hotel::withTrashed()->where('id_hotel_cangooroo', $hotelInfo['id_hotel_cangooroo'])->first();

        if($hotel){
            $hotel['deleted_at'] = null;
            $hotel->fill($hotelInfo)->save();
        }
        else{
            $hotel = new Hotel;
            $hotel = $hotel->create($hotelInfo);
        }

        $this->syncBankAccounts($hotel, $hotelInfo);
        return $this->hotel->with($this->with)->findOrFail($hotel->id);
    }

    public function putHotel($id, $hotelInfo)
    {
        $hotel = $this->hotel->findOrFail($id);
        $hotel->fill($hotelInfo)->save();
        $this->putBankAccounts($id, $hotelInfo);
        return $this->hotel->with($this->with)->findOrFail($hotel->id);
    }

    public function deleteHotel($id)
    {
        $hotels = $this->hotel->findOrFail($id)->delete();
        $collection = $this->hotelHasBankAccounts->where('hotel_id', $id)->get(['bank_account_id']);
        $this->bankAccount->destroy($collection->toArray());
        return true;
    }

    public function putBankAccounts($id, $hotelInfo)
    {

        $updateBankAccounts = [];
        $createdBankAccounts = [];

        if (array_key_exists('bank_accounts', $hotelInfo)) {
            $attachArray = [];

            foreach ($hotelInfo['bank_accounts'] as $bank) {
                if (array_key_exists('id', $bank)) {
                    $bankAccount = $this->bankAccount->with('hotel_bank_account_default')->findOrFail($bank['id']);
                    $bankAccount->fill($bank)->save();
                    $updateBankAccounts[] = $bank['id'];
                    $hotelHasBankAccount = HotelHasBankAccounts::findOrFail($bankAccount->hotel_bank_account_default->id);
                    $hotelHasBankAccount->fill($bank)->save();
                } else {
                    $bankAccount = new BankAccount;
                    $bankAccount = $bankAccount->create($bank);
                    $attachArray[] = [
                        'bank_account_id' => $bankAccount->id,
                        'default_bank' => $bank['default_bank'] ?? false,
                    ];
                    $createdBankAccounts[] = $bankAccount->id;
                }
            }

            $collection = $this->hotelHasBankAccounts
                ->where('hotel_id', $id)
                ->whereNotIn('bank_account_id', $updateBankAccounts)
                ->whereNotIn('bank_account_id', $createdBankAccounts)
                ->get(['bank_account_id']);
            $this->bankAccount->destroy($collection->toArray());

            $hotel = $this->hotel->findOrFail($id);
            $hotel->bank_account()->attach($attachArray);
        }
    }

    public function syncBankAccounts($hotel, $hotelInfo)
    {
        $syncArray = [];
        if (array_key_exists('bank_accounts', $hotelInfo)) {
            foreach ($hotelInfo['bank_accounts'] as $bank) {
                $bankAccount = new BankAccount;
                $bankAccount = $bankAccount->create($bank);
                $syncArray[] = [
                    'bank_account_id' => $bankAccount->id,
                    'default_bank' => $bank['default_bank'] ?? false,
                ];
            }
            $hotel->bank_account()->sync($syncArray);
        }
    }
}
