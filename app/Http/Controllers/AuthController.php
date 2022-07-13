<?php

namespace App\Http\Controllers;

use App\Services\AuthService as AuthService;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

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
            return response(['error'=>'Usuário ou senha inválido.'], 422);
        }

        $tokenParts = explode(".", $tokenResponse->access_token);
        $tokenHeader = base64_decode($tokenParts[0]);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtHeader = json_decode($tokenHeader);
        $jwtPayload = json_decode($tokenPayload);

        return $this->authService->getUser($jwtPayload->sub, $tokenResponse);
    }

    public function log(Request $request)
    {
        $requestInfo = $request->all();

        $logs = Activity::where('log_name', 'payment_request')->where('id', 3855)->get();

        foreach ($logs as $log)
        {

            $decodedLog = json_decode($log, true);

            $decodedLog['properties']['attributes']['provider']['bank_account'] = [];
            $logUpdate = Activity::findOrFail($log->id);
            $logUpdate->update(['properties' => $decodedLog['properties']]);


            $logUpdate = Activity::findOrFail($log->id);
            dd($logUpdate->properties['attributes']['provider']['bank_account']);
        }

        dd('deu certo');
    }
}
