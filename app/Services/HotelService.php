<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\BankAccount;
use App\Models\Billing;
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
        if (array_key_exists('isValid', $requestInfo)) {
            $hotel->where('is_valid', $requestInfo['isValid']);
        }
        if (array_key_exists('cnpj_hotel', $requestInfo)) {
            $hotel->where('cnpj_hotel', $requestInfo['cnpj_hotel']);
        }
        if (array_key_exists('cnpj', $requestInfo)) {
            $hotel->where(function ($query) use ($requestInfo) {
                $query->where('cpf_cnpj', $requestInfo['cnpj'])
                      ->orWhere('cnpj_extra', $requestInfo['cnpj']);
            });
        }
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

        $stage = $hotelInfo['is_valid'] ? 1 : 0;

        $this->syncBankAccounts($hotel, $hotelInfo);
        Utils::createHotelLog($hotel->id, 'created', null, null, $stage, auth()->user()->id);
        return $this->hotel->with($this->with)->findOrFail($hotel->id);
    }

    public function putHotel($id, $hotelInfo)
    {
        $hotel = $this->hotel->findOrFail($id);
        $hotelOld = $this->hotel->with($this->with)->findOrFail($id);
        if($hotelInfo['cnpj_hotel'] != $hotel['cnpj_hotel'] || $hotelInfo['cpf_cnpj'] != $hotel['cpf_cnpj'] || $hotelInfo['holder_full_name'] != $hotel['holder_full_name'] || ($hotelInfo['cnpj_extra'] && ($hotelInfo['cnpj_extra'] != $hotel['cnpj_extra']))){
            $hotelInfo['is_valid'] = false;
        }
        activity()->disableLogging();
        $hotel->fill($hotelInfo)->save();
        activity()->enableLogging();
        $this->putBankAccounts($id, $hotelInfo);
        $hotelNew = $this->hotel->with($this->with)->findOrFail($id);
        $stage = $hotelInfo['is_valid'] ? 1 : 0;
        Utils::createManualLog($hotelOld, $hotelNew, auth()->user()->id, $this->hotel, 'hotels');
        Utils::createHotelLog($hotel->id, 'updated', null, null, $stage, auth()->user()->id);
        return $this->hotel->with($this->with)->findOrFail($hotel->id);
    }

    public function deleteHotel($id)
    {
        $hotel = $this->hotel->findOrFail($id);
        $billing = Billing::whereHas('cangooroo', function ($query) use ($hotel) {
            $query->where('hotel_id', $hotel->id_hotel_cangooroo);
        })->first();
        if(!$billing){
            Utils::createHotelLog($hotel->id, 'deleted', null, null, 0, auth()->user()->id);
            $hotel->delete();
            $collection = $this->hotelHasBankAccounts->where('hotel_id', $id)->get(['bank_account_id']);
            $this->bankAccount->destroy($collection->toArray());
            return '';
        }
        else{
            return response()->json([
                'error' => 'Existem reservas abertas para esse hotel',
            ], 422);
        }
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
