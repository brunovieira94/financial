<?php

namespace App\Http\Controllers;

use App\Models\AccountsPayableApprovalFlow;
use App\Models\PaymentRequest;
use App\Models\Provider;
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

        // dd($paymentRequestsIDs);

        $logsPaymentRequest = Activity::where('log_name', 'payment_request')->whereIn('subject_id', $paymentRequestsIDs)->get();

        foreach ($logsPaymentRequest as $log) {
            //dd($log->id);

            $decodedLog = json_decode($log, true);
            if ($decodedLog['log_name'] == 'payment_request') {
                if ($decodedLog['description'] == 'created' or $decodedLog['description'] == 'deleted') {
                    if (array_key_exists('properties', $decodedLog)) {
                        if (array_key_exists('attributes', $decodedLog['properties'])) {
                            if (array_key_exists('provider', $decodedLog['properties']['attributes'])) {
                                if (array_key_exists('bank_account', $decodedLog['properties']['attributes']['provider'])) {
                                    $decodedLog['properties']['attributes']['provider']['bank_account'] = [];
                                    $logUpdate = Activity::findOrFail($log->id);
                                    $logUpdate->update(['properties' => $decodedLog['properties']]);
                                }
                            }
                        }
                    }
                } else if ($decodedLog['description'] == 'updated') {
                    if (array_key_exists('properties', $decodedLog)) {
                        if (array_key_exists('old', $decodedLog['properties'])) {
                            if (array_key_exists('provider', $decodedLog['properties']['attributes'])) {
                                if (array_key_exists('bank_account', $decodedLog['properties']['attributes']['provider'])) {
                                    $decodedLog['properties']['attributes']['provider']['bank_account'] = [];
                                    $logUpdate = Activity::findOrFail($log->id);
                                    $logUpdate->update(['properties' => $decodedLog['properties']]);
                                }
                            }
                        }
                        if (array_key_exists('attributes', $decodedLog['properties'])) {
                            if (array_key_exists('provider', $decodedLog['properties']['attributes'])) {
                                if (array_key_exists('bank_account', $decodedLog['properties']['attributes']['provider'])) {
                                    $decodedLog['properties']['attributes']['provider']['bank_account'] = [];
                                    $logUpdate = Activity::findOrFail($log->id);
                                    $logUpdate->update(['properties' => $decodedLog['properties']]);
                                }
                            }
                        }
                    }
                }
            }
        }

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

        return response(['Sucesso' => 'Contas alteradas'], 200);
    }
}
