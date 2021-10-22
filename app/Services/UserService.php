<?php

namespace App\Services;
use App\Models\User;
use App\Models\CostCenter;
use App\Models\Business;
use Illuminate\Support\Facades\Hash;

class UserService
{
    private $user;
    private $costCenter;
    private $business;
    public function __construct(User $user, CostCenter $costCenter, Business $business)
    {
        $this->user = $user;
        $this->costCenter = $costCenter;
        $this->business = $business;
    }

    public function getAllUser($requestInfo)
    {
        $user = Utils::search($this->user,$requestInfo);
        return Utils::pagination($user->with(['costCenter', 'business', 'role']),$requestInfo);
    }

    public function getUser($id)
    {
      return $this->user->with(['costCenter', 'business', 'role'])->findOrFail($id);
    }

    public function postUser($userInfo)
    {
        $user = new User;
        $userInfo['password'] = Hash::make($userInfo['password']);
        $user = $user->create($userInfo);

        self::syncBusiness($user, $userInfo);
        self::syncCostCenter($user, $userInfo);
        return $this->user->with(['costCenter', 'business', 'role'])->findOrFail($user->id);

    }

    public function putUser($id, $userInfo)
    {
        $user = $this->user->findOrFail($id);

        if(array_key_exists('password', $userInfo)){
            $userInfo['password'] = Hash::make($userInfo['password']);
        }
        $user->fill($userInfo)->save();

        self::syncCostCenter($user, $userInfo);
        self::syncBusiness($user, $userInfo);

        return $this->user->with(['costCenter', 'business', 'role'])->findOrFail($id);
    }

    public function deleteUser($id)
    {
      $this->user->findOrFail($id)->delete();
      return true;
    }

    public function syncCostCenter($user, $userInfo){
        if(array_key_exists('cost_centers', $userInfo)){
            $user->costCenter()->sync($userInfo['cost_centers']);
        }
    }

    public function syncBusiness($user, $userInfo){
        if(array_key_exists('business', $userInfo)){
            $user->business()->sync($userInfo['business']);
        }
    }
}
