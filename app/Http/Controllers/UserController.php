<?php

namespace App\Http\Controllers;

use App\Http\Requests\PutMyUserRequest;
use Illuminate\Http\Request;
use App\Services\UserService as UserService;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\PutUserRequest;

class UserController extends Controller
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        return $this->userService->getAllUser($request->all());
    }

    public function show($id)
    {
        return $this->userService->getUser($id);
    }

    public function store(StoreUserRequest $request)
    {
        return $this->userService->postUser($request->all());
    }

    public function update(PutUserRequest $request, $id)
    {
        return $this->userService->putUser($id, $request->all());
    }

    public function destroy($id)
    {
        $user = $this->userService->deleteUser($id);
        return response('');
    }

    public function updateMyUser(PutMyUserRequest $request)
    {
        return $this->userService->updateMyUser($request->all());
    }

}
