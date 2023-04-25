<?php

namespace App\Services;

use App\Models\AdditionalUser;
use App\Models\User;
use App\Models\CostCenter;
use App\Models\Business;
use App\Models\UserHasSavedFilter;
use Illuminate\Support\Facades\Hash;

class UserService
{
    private $user;
    private $costCenter;
    private $business;
    private $with = ['cost_center', 'business', 'role', 'additional_users.role', 'filters'];

    public function __construct(User $user, CostCenter $costCenter, Business $business)
    {
        $this->user = $user;
        $this->costCenter = $costCenter;
        $this->business = $business;
    }

    public function getAllUser($requestInfo)
    {
        $user = Utils::search($this->user, $requestInfo);
        if (array_key_exists('cost_center', $requestInfo)) {
            if (!empty($requestInfo['cost_center'])) {
                $user = $user->whereHas('cost_center', function ($query) use ($requestInfo) {
                    $query->where('cost_center_id', $requestInfo['cost_center']);
                });
            }
        }
        if (array_key_exists('status', $requestInfo)) {
            if (!empty($requestInfo['status']) or $requestInfo['status'] == 0) {
                if (!is_null($requestInfo['status'])) {
                    $user = $user->where('status', $requestInfo['status']);
                }
            }
        }
        if (array_key_exists('not_active', $requestInfo)) {
            $user = $user->where('status', '!=', 0);
        }

        return Utils::pagination($user->with($this->with), $requestInfo);
    }

    public function getUser($id)
    {
        return $this->user->with($this->with)->findOrFail($id);
    }

    public function postUser($userInfo)
    {
        $user = new User;
        $userInfo['password'] = Hash::make($userInfo['password']);
        $user = $user->create($userInfo);

        self::syncAdditionalUsers($user, $userInfo);
        self::syncBusiness($user, $userInfo);
        self::syncCostCenter($user, $userInfo);
        self::syncFilters($user, $userInfo);

        return $this->user->with($this->with)->findOrFail($user->id);
    }

    public function putUser($id, $userInfo)
    {
        $user = $this->user->findOrFail($id);

        if (array_key_exists('password', $userInfo)) {
            $userInfo['password'] = Hash::make($userInfo['password']);
        }

        $user->fill($userInfo)->save();

        self::syncAdditionalUsers($user, $userInfo);
        self::syncCostCenter($user, $userInfo);
        self::syncBusiness($user, $userInfo);
        self::syncFilters($user, $userInfo);

        $user = $this->user->with($this->with)->findOrFail($id);

        if ($user->status == 0) {
            AdditionalUser::where('user_additional_id', $user->id)->delete();
            User::where('id', $user->id)
                ->update(['return_date' => null]);
        }
        return $user;
    }

    public function deleteUser($id)
    {
        $this->user->findOrFail($id)->delete();
        return true;
    }

    public function updateMyUser($userInfo)
    {

        $user = User::findOrFail(auth()->user()->id);

        if (array_key_exists('password', $userInfo) && array_key_exists('new-password', $userInfo)) {
            if (!Hash::check($userInfo['password'], auth()->user()->password)) {
                return Response()->json([
                    'error' => 'A senha informada é inválida',
                ], 422);
            }

            if (array_key_exists('new-password', $userInfo)) {
                $userInfo['password'] = Hash::make($userInfo['new-password']);
            } else {
                unset($userInfo['password']);
            }
        } else {
            unset($userInfo['password']);
        }

        $user->fill($userInfo)->save();
        self::syncFilters($user, $userInfo);

        return $this->user->with($this->with)->findOrFail(auth()->user()->id);
    }

    public function syncCostCenter($user, $userInfo)
    {
        if (array_key_exists('cost_centers', $userInfo)) {
            $user->cost_center()->sync($userInfo['cost_centers']);
        }
    }

    public function syncBusiness($user, $userInfo)
    {
        if (array_key_exists('business', $userInfo)) {
            $user->business()->sync($userInfo['business']);
        }
    }

    public function syncAdditionalUsers($user, $userInfo)
    {
        if (array_key_exists('additional_users', $userInfo)) {
            $user->additional_users()->sync($userInfo['additional_users']);
        }
    }

    public function syncFilters($user, $userInfo)
    {
        if (array_key_exists('filters', $userInfo)) {
            UserHasSavedFilter::where('user_id', auth()->user()->id)->delete();
            foreach($userInfo['filters'] as $filter){
                $filter['user_id'] = auth()->user()->id;
                UserHasSavedFilter::create($filter);
            }
        }
    }
}
