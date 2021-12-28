<?php

namespace App\Http\Controllers;

use App\Services\AuthService as AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        $request->request->add([
            'client_id' => env('TOKEN_CLIENT_ID'),
            'client_secret' => env('TOKEN_CLIENT_SECRET')
        ]);
        $proxy = Request::create('oauth/token', 'POST', $request->input());
        $response = app()->handle($proxy);

        $tokenResponse = json_decode($response->content());

        if(!$tokenResponse)
        {
            return response(['mensagem'=>'Credencial inválida'], 422);
        }

        $tokenParts = explode(".", $tokenResponse->access_token);
        $tokenHeader = base64_decode($tokenParts[0]);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtHeader = json_decode($tokenHeader);
        $jwtPayload = json_decode($tokenPayload);

        return $this->authService->getUser($jwtPayload->sub, $tokenResponse);
    }
}
