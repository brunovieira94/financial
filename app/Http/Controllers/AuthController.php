<?php

namespace App\Http\Controllers;

use App\Models\AccountsPayableApprovalFlow;
use App\Models\PaymentRequest;
use App\Models\Provider;
use App\Models\User;
use App\Services\AuthService as AuthService;
use Exception;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\TryCatch;
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
        if (User::where('email', $request->username)->exists()) {
            $user = User::where('email', $request->username)->first();
            $hash = app('hash');
            if ($hash->check($request->password, $user->password)) {
                User::where('id', $user->id)
                    ->update(['logged_user_id' => null]);
            }
        }

        $request->request->add([
            'client_id' => env('TOKEN_CLIENT_ID'),
            'client_secret' => env('TOKEN_CLIENT_SECRET')
        ]);
        $proxy = Request::create('oauth/token', 'POST', $request->input());
        $response = app()->handle($proxy);

        $tokenResponse = json_decode($response->content());

        if (!$tokenResponse) {
            return response(['error' => 'Usuário ou senha inválido.'], 422);
        }

        $tokenParts = explode(".", $tokenResponse->access_token);
        $tokenHeader = base64_decode($tokenParts[0]);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtHeader = json_decode($tokenHeader);
        $jwtPayload = json_decode($tokenPayload);

        return $this->authService->getUser($jwtPayload->sub, $tokenResponse);
    }

    public function changeLogin(Request $request, $id)
    {
        return $this->authService->changeLogin($request, $id);
    }

    public function log(Request $request)
    {
        $requestInfo = $request->all();

        if (!array_key_exists('provider_id', $requestInfo)) {
            return response(['erro' => 'É necessário informar o id do fornecedor'], 422);
        }

        if (!Provider::findOrFail($requestInfo['provider_id'])->generic_provider) {
            return response(['erro' => 'Só é permitido fornecedores genéricos para está ação'], 422);
        }

        $paymentRequestsIDs = PaymentRequest::with('provider')->whereRelation('provider', 'id', '=', $requestInfo['provider_id'])->withoutGlobalScopes()->get('id');
        $paymentRequestsIDs = $paymentRequestsIDs->pluck('id');

        $approvalFlowIDs = AccountsPayableApprovalFlow::whereIn('payment_request_id', $paymentRequestsIDs)->get('id');
        $approvalFlowIDs = $approvalFlowIDs->pluck('id');

        $totalUpdated = 0;
        $totalCreated = 0;
        $logsIDs = [];

        foreach ($paymentRequestsIDs as $idPayment) {
            $idLogs = Activity::where('log_name', 'payment_request')->where('subject_id', $idPayment)->get('id');
            $idLogs = $idLogs->pluck('id');

            foreach ($idLogs as $logID) {

                $log = Activity::findOrFail($logID);
                $decodedLog = json_decode($log->properties, true);

                if ($log->log_name == 'payment_request') {
                    if ($log->description == 'created' or $log->description == 'deleted') {
                        if (array_key_exists('provider', $decodedLog['attributes'])) {
                            if (array_key_exists('bank_account', $decodedLog['attributes']['provider'])) {
                                $decodedLog['attributes']['provider']['bank_account'] = [];
                                $log->properties = $decodedLog;
                                $log->save();
                                $totalCreated++;
                            }
                        }
                    } else if ($log->description == 'updated') {
                        if (array_key_exists('old', $decodedLog)) {
                            if (array_key_exists('provider', $decodedLog['attributes'])) {
                                if (array_key_exists('bank_account', $decodedLog['attributes']['provider'])) {
                                    $decodedLog['attributes']['provider']['bank_account'] = [];
                                    $log->properties = $decodedLog;
                                    $log->save();
                                    $totalUpdated++;
                                }
                            }
                            if (array_key_exists('attributes', $decodedLog)) {
                                if (array_key_exists('provider', $decodedLog['attributes'])) {
                                    if (array_key_exists('bank_account', $decodedLog['attributes']['provider'])) {
                                        $decodedLog['attributes']['provider']['bank_account'] = [];
                                        $log->properties = $decodedLog;
                                        $log->save();
                                    }
                                }
                            }
                        }
                    }
                }
                array_push($logsIDs, $log->id);
            }
        }

        //$logsPaymentRequest = Activity::where('log_name', 'payment_request')->whereIn('subject_id', $paymentRequestsIDs)->get();


        /*

        $logsApprovalFlow = Activity::where('log_name', 'accounts_payable_approval_flows')->whereIn('subject_id', $approvalFlowIDs)->get();
        foreach ($logsApprovalFlow as $log) {

            $decodedLog = json_decode($log, true);
            if ($decodedLog['log_name'] == 'accounts_payable_approval_flows') {
                if ($decodedLog['description'] == 'updated') {
                    if (array_key_exists('properties', $decodedLog)) {
                        if (array_key_exists('old', $decodedLog['properties'])) {
                            if (array_key_exists('payment_request', $decodedLog['properties']['old']) && $decodedLog['properties']['old']['payment_request'] != null) {
                                if (array_key_exists('provider', $decodedLog['properties']['old']['payment_request']) && $decodedLog['properties']['old']['payment_request']['provider'] != null) {
                                    if (array_key_exists('bank_account', $decodedLog['properties']['old']['payment_request']['provider'])) {
                                        $decodedLog['properties']['old']['payment_request']['provider']['bank_account'] = [];
                                        $logUpdate = Activity::findOrFail($log->id);
                                        $logUpdate->update(['properties' => $decodedLog['properties']]);
                                    }
                                }
                            }
                        }
                        if (array_key_exists('attributes', $decodedLog['properties'])) {
                            if (array_key_exists('payment_request', $decodedLog['properties']['attributes']) && $decodedLog['properties']['attributes']['payment_request'] != null) {
                                if (array_key_exists('provider', $decodedLog['properties']['attributes']['payment_request'])) {
                                    if (array_key_exists('bank_account', $decodedLog['properties']['attributes']['payment_request']['provider'])) {
                                        $decodedLog['properties']['attributes']['payment_request']['provider']['bank_account'] = [];
                                        $logUpdate = Activity::findOrFail($log->id);
                                        $logUpdate->update(['properties' => $decodedLog['properties']]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        */

        return response([
            'Sucesso' => 'Contas alteradas',
            'Criado' => $totalCreated,
            'Atualizado' => $totalUpdated,
            'ID Logs' => $logsIDs,
            'Solicitação de Pagamento' => $paymentRequestsIDs
        ], 200);
    }
}
