<?php

namespace App\Services;

use App\Models\AccountsPayableApprovalFlow;
use App\Models\AccountsPayableApprovalFlowClean;
use App\Models\AccountsPayableApprovalFlowLog;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowSupply;
use App\Models\PaidBillingInfo;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDelivery;
use App\Models\SupplyApprovalFlow;
use App\Models\HotelApprovalFlow;
use App\Models\PaymentRequestHasInstallmentsClean;
use App\Models\Role;
use App\Models\User;
use App\Models\Billing;
use App\Models\BillingLog;
use App\Models\HotelLog;
use App\Models\NotificationCatalog;
use App\Models\NotificationCatalogHasRoles;
use App\Models\NotificationCatalogHasUsers;
use App\Models\PaymentRequestClean;
use App\Models\PaymentRequestHasInstallmentLinked;
use App\Models\PaymentRequestHasInstallments;
use Carbon\Carbon;
use Config;
use DateTime;
use DB;
use Exception;
use Faker\Provider\ar_EG\Payment;
use Spatie\Activitylog\Contracts\Activity;
use Storage;

class Utils
{
    const defaultPerPage = 20;
    const defaultOrderBy = 'id';
    const defaultOrder = 'desc';

    public static function pagination($model, $requestInfo)
    {
        $orderBy = $requestInfo['orderBy'] ?? self::defaultOrderBy;
        $order = $requestInfo['order'] ?? self::defaultOrder;
        $perPage = $requestInfo['perPage'] ?? self::defaultPerPage;
        $columnRelationshipOrderBy = explode('.', $orderBy);
        if (count($columnRelationshipOrderBy) == 1) {
            $model = $model->orderBy($orderBy, $order);
        } else {
            $columnFilter = last($columnRelationshipOrderBy);
            $table = '';
            switch ($columnRelationshipOrderBy[0]) {
                case 'provider':
                    $table = 'providers';
                    break;
                case 'company':
                    $table = 'companies';
                    break;
                case 'cost_center':
                    $table = 'cost_center';
                    break;
                case 'chart_of_accounts':
                    $table = 'chart_of_accounts';
                    break;
            }
            $model
                ->join('providers', 'providers.id', '=', 'payment_requests.provider_id')
                ->join('companies', 'companies.id', '=', 'payment_requests.company_id')
                ->join('cost_center', 'cost_center.id', '=', 'payment_requests.cost_center_id')
                ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'payment_requests.chart_of_account_id')
                ->orderBy($table . '.' . $columnFilter, $order);
        }

        return $model->paginate($perPage);
    }

    public static function getDeleteKeys($nestable)
    {
        $arrayIds = [];
        foreach ($nestable as $key => $value) {
            array_push($arrayIds, $nestable[$key]['id']);
            if (sizeof($nestable[$key]['children']) > 0) {
                $auxArray = self::getDeleteKeys($nestable[$key]['children']);
                foreach ($auxArray as $element) {
                    array_push($arrayIds, $element);
                }
            }
        }
        return $arrayIds;
    }

    public static function validateDate($date, $format = 'd/m/Y')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    public static function formatDate($date)
    {
        $date = explode('/', $date);
        $year = $date[2];
        $date[2] = $date[0];
        $date[0] = $year;
        return $date = implode('-', $date);
    }

    public static function search($model, $requestInfo, $excludeFields = null)
    {
        $fillable = $model->getFillable();
        if ($excludeFields != null) {
            foreach ($fillable as $key => $value) {
                if (in_array($fillable[$key], $excludeFields)) {
                    unset($fillable[$key]);
                }
            }
        }
        $query = $model->query();
        if (array_key_exists('search', $requestInfo)) {

            if (self::validateDate($requestInfo['search'], 'd/m/Y')) {
                $requestInfo['search'] = self::formatDate($requestInfo['search']);
            }
            if (array_key_exists('searchFields', $requestInfo)) {
                $query->whereLike($requestInfo['searchFields'], "%{$requestInfo['search']}%");
            } else {
                $query->whereLike($fillable, "%{$requestInfo['search']}%");
            }
        }
        return $query;
    }

    public static function groupInstallments($installments, $bankCode)
    {
        $groupInstallment = [];
        foreach ($installments as $installment) {
            try {
                foreach ($installment->group_payment->form_payment as $payment_form) {
                    if ($payment_form->bank_code == $bankCode) {
                        if ($payment_form->group_form_payment_id == 2) //Default PIX group 2
                        {
                            if (PaymentRequestHasInstallmentsClean::has('bank_account_provider')->where('id', $installment['id'])->exists()) {
                                if (array_key_exists('45', $groupInstallment)) {
                                    array_push($groupInstallment[$payment_form->code_cnab], $installment);
                                    break;
                                } else {
                                    $groupInstallment['45'] = [$installment];
                                    break;
                                }
                            } else {
                                PaymentRequestHasInstallmentsClean::where('id', $installment['id'])->update(['status' => Config::get('constants.status.error')]);
                            }
                        } elseif ($payment_form->group_form_payment_id == 1) {
                            if ($installment->type_billet == 4) {
                                if ($payment_form->concessionaire_billet) {
                                    if (array_key_exists($payment_form->code_cnab, $groupInstallment)) {
                                        array_push($groupInstallment[$payment_form->code_cnab], $installment);
                                        break;
                                    } else {
                                        $groupInstallment[$payment_form->code_cnab] = [$installment];
                                        break;
                                    }
                                }
                            } else {
                                if (substr($installment->bar_code, 0, 3) == $bankCode) {
                                    if ($payment_form->same_ownership && $payment_form->concessionaire_billet == false) {
                                        if (array_key_exists($payment_form->code_cnab, $groupInstallment)) {
                                            array_push($groupInstallment[$payment_form->code_cnab], $installment);
                                            break;
                                        } else {
                                            $groupInstallment[$payment_form->code_cnab] = [$installment];
                                            break;
                                        }
                                    }
                                } else {
                                    if (!$payment_form->same_ownership && $payment_form->concessionaire_billet == false) {
                                        if (array_key_exists($payment_form->code_cnab, $groupInstallment)) {
                                            array_push($groupInstallment[$payment_form->code_cnab], $installment);
                                            break;
                                        } else {
                                            $groupInstallment[$payment_form->code_cnab] = [$installment];
                                            break;
                                        }
                                    }
                                }
                            }
                        } else {
                            if ($installment->bank_account_provider->bank->bank_code == $bankCode) {
                                if ($payment_form->same_ownership) {
                                    if (array_key_exists($payment_form->code_cnab, $groupInstallment)) {
                                        array_push($groupInstallment[$payment_form->code_cnab], $installment);
                                        break;
                                    } else {
                                        $groupInstallment[$payment_form->code_cnab] = [$installment];
                                        break;
                                    }
                                }
                            } else {
                                if (!$payment_form->same_ownership) {
                                    if (array_key_exists($payment_form->code_cnab, $groupInstallment)) {
                                        array_push($groupInstallment[$payment_form->code_cnab], $installment);
                                        break;
                                    } else {
                                        $groupInstallment[$payment_form->code_cnab] = [$installment];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                PaymentRequestHasInstallmentsClean::where('id', $installment['id'])->update(['status' => Config::get('constants.status.error')]);
            }
        }
        return $groupInstallment;
    }

    public static function formatCnab($tipo, $valor, $tamanho, $dec = 0, $sFill = '')
    {
        $tipo = self::upper($tipo);
        $valor = self::upper(self::normalizeChars($valor));
        if (in_array($tipo, array('9', 9, 'N', '9L', 'NL'))) {
            if ($tipo == '9L' || $tipo == 'NL') {
                $valor = self::onlyNumbers($valor);
            }
            $left = '';
            $sFill = 0;
            $type = 's';
            $valor = ($dec > 0) ? sprintf("%.{$dec}f", $valor) : $valor;
            $valor = str_replace(array(',', '.'), '', $valor);
        } elseif (in_array($tipo, array('A', 'X'))) {
            $left = '-';
            $type = 's';
        } else {
            throw new \Exception('Tipo inválido');
        }
        return sprintf("%{$left}{$sFill}{$tamanho}{$type}", mb_substr($valor, 0, $tamanho));
    }

    public static function upper($string)
    {
        return strtr(mb_strtoupper($string), "àáâãäåæçèéêëìíîïðñòóôõö÷øùüúþÿ", "ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÜÚÞß");
    }

    public static function normalizeChars($string)
    {
        $normalizeChars = array(
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Å' => 'A', 'Ä' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ð' => 'Eth',
            'Ñ' => 'N', 'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Ŕ' => 'R',

            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a', 'ä' => 'a', 'æ' => 'ae', 'ç' => 'c',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'eth',
            'ñ' => 'n', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ŕ' => 'r', 'ÿ' => 'y',

            'ß' => 'sz', 'þ' => 'thorn', 'º' => '', 'ª' => '', '°' => '',
        );
        return preg_replace('/[^0-9a-zA-Z !*\-$\(\)\[\]\{\},.;:\/\\#%&@+=]/', '', strtr($string, $normalizeChars));
    }

    public static function onlyNumbers($string)
    {
        return self::numbersOnly($string);
    }

    public static function numbersOnly($string)
    {
        return preg_replace('/[^[:digit:]]/', '', $string);
    }

    public static function centralizadoraBB($codeBank)
    {
        switch ($codeBank) {
            case 43:
                return '018';
                break;
            case 41:
                return '018';
                break;
            case 45:
                return '009';
                break;
            case 03:
                return '700';
                break;
            default:
                return '000';
        }
    }

    public static function codigoBarrasBB($linhaDigitavel)
    {
        $linhaDigitavel = self::onlyNumbers($linhaDigitavel);
        switch (strlen($linhaDigitavel)) {
            case 47:
                return substr($linhaDigitavel, 0, 4) . substr($linhaDigitavel, 32, 15) . substr($linhaDigitavel, 4, 5) . substr($linhaDigitavel, 10, 10) . substr($linhaDigitavel, 21, 10);
                break;
            case 44:
                return $linhaDigitavel;
                break;
            case 48:
                return substr($linhaDigitavel, 0, 11) . substr($linhaDigitavel, 12, 11) . substr($linhaDigitavel, 24, 11) . substr($linhaDigitavel, 36, 11);
                break;
            default:
                return $linhaDigitavel;
        }
    }


    public static function identificacaoTipoTransferencia($tipoConta)
    {
        switch ($tipoConta) {
            case 0:
                return '03'; // POUPANÇA
                break;
            case 1:
                return '01'; // C CORRENTE
                break;
            case 2:
                return 'PG'; // C SALÁRIO
                break;
            case 3:
                return '04'; // PIX
                break;
        }
    }

    public static function codigoBancoFavorecidoBoleto($boleto)
    {
        return substr($boleto, 0, 3);
    }

    public static function codigoMoedaBoleto($boleto)
    {
        return substr($boleto, 3, 1);
    }

    public static function dvBoleto($boleto)
    {
        return substr($boleto, 32, 1);
    }

    public static function valorBoleto($boleto)
    {
        return substr($boleto, 37, 10);
    }

    public static function campoLivreBoleto($boleto)
    {
        $primeiroCampoLivre = substr($boleto, 4, 5);
        $segundoCampoLivre = substr($boleto, 10, 10);
        $terceiroCampoLivre = substr($boleto, 21, 10);
        return "{$primeiroCampoLivre}{$segundoCampoLivre}{$terceiroCampoLivre}";
    }

    public static function fatorVencimentoBoleto($boleto)
    {
        return substr($boleto, 33, 4);
    }

    public static array $approvalStatus = [
        "billing-open",
        "billing-approved",
        "billing-rejected",
        "billing-canceled",
        "billing-paid",
        "billing-error",
        "billing-cnab-generated",
        "billing-finished"
    ];

    public static function baseFilterReportsPaymentRequest($paymentRequest, $requestInfo, $installment = false)
    {
        if (array_key_exists('provider', $requestInfo)) {
            $paymentRequest = $paymentRequest->where('provider_id', $requestInfo['provider']);
        }
        if (array_key_exists('net_value', $requestInfo)) {
            if (!$installment) {
                $paymentRequest = $paymentRequest->where('net_value', $requestInfo['net_value']);
            }
        }
        if (array_key_exists('company', $requestInfo)) {
            $paymentRequest = $paymentRequest->where('company_id', $requestInfo['company']);
        }
        if (array_key_exists('cost_center', $requestInfo)) {
            $paymentRequest = $paymentRequest->where('cost_center_id', $requestInfo['cost_center']);
        }
        if (array_key_exists('cpfcnpj', $requestInfo)) {
            $paymentRequest = $paymentRequest->whereHas('provider', function ($query) use ($requestInfo) {
                $query->where('cpf', $requestInfo['cpfcnpj'])->orWhere('cnpj', $requestInfo['cpfcnpj']);
            });
        }
        if (array_key_exists('chart_of_accounts', $requestInfo)) {
            $paymentRequest = $paymentRequest->where('chart_of_account_id', $requestInfo['chart_of_accounts']);
        }
        if (array_key_exists('payment_request', $requestInfo)) {
            $paymentRequest = $paymentRequest->where('id', $requestInfo['payment_request']);
        }
        if (array_key_exists('user', $requestInfo)) {
            $paymentRequest = $paymentRequest->where('user_id', $requestInfo['user']);
        }
        if (array_key_exists('created_at', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['created_at'])) {
                $paymentRequest = $paymentRequest->where('created_at', '>=', $requestInfo['created_at']['from']);
            }
            if (array_key_exists('to', $requestInfo['created_at'])) {
                $paymentRequest = $paymentRequest->where('created_at', '<=', date("Y-m-d", strtotime("+1 days", strtotime($requestInfo['created_at']['to']))));
            }
            if (!array_key_exists('to', $requestInfo['created_at']) && !array_key_exists('from', $requestInfo['created_at'])) {
                $paymentRequest = $paymentRequest->whereBetween('created_at', [now()->addMonths(-1), now()]);
            }
        }
        if (array_key_exists('pay_date', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['pay_date'])) {
                $paymentRequest = $paymentRequest->where('pay_date', '>=', $requestInfo['pay_date']['from']);
            }
            if (array_key_exists('to', $requestInfo['pay_date'])) {
                $paymentRequest = $paymentRequest->where('pay_date', '<=', $requestInfo['pay_date']['to']);
            }
            if (!array_key_exists('to', $requestInfo['pay_date']) && !array_key_exists('from', $requestInfo['pay_date'])) {
                $paymentRequest = $paymentRequest->whereBetween('pay_date', [now(), now()->addMonths(1)]);
            }
        }
        /*if (array_key_exists('extension_date', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['extension_date'])) {
                $paymentRequest = $paymentRequest->whereHas('installments', function ($installments) use ($requestInfo) {
                    if (array_key_exists('from', $requestInfo['extension_date'])) {
                        $installments->where('extension_date', '>=', $requestInfo['extension_date']['from']);
                    }
                    if (array_key_exists('to', $requestInfo['extension_date'])) {
                        $installments->where('extension_date', '<=', $requestInfo['extension_date']['to']);
                    }
                    if (!array_key_exists('to', $requestInfo['extension_date']) && !array_key_exists('from', $requestInfo['extension_date'])) {
                        $installments->whereBetween('extension_date', [now(), now()->addMonths(1)]);
                    }
                });
            }
        }*/
        if (array_key_exists('days_late', $requestInfo)) {
            $paymentRequest = $paymentRequest->whereHas('installments', function ($query) use ($requestInfo) {
                $query->where('status', '!=', Config::get('constants.status.paid out'))->orWhereNull('status')->whereDate("due_date", "<=", Carbon::now()->subDays($requestInfo['days_late']));
            });
        }

        if (array_key_exists('approval_order', $requestInfo)) {
            $paymentRequest = $paymentRequest->whereHas('approval', function ($query) use ($requestInfo) {
                $query->where('order', $requestInfo['approval_order']);
            });
        }

        if (array_key_exists('status', $requestInfo)) {
            if (is_array($requestInfo['status']) && !empty($requestInfo['status'])) {
                $arrayInt = [];
                foreach ($requestInfo['status'] as $val) {
                    array_push($arrayInt, (int) $val);
                }
                $requestInfo['status'] = $arrayInt;
                if (in_array(3, $requestInfo['status'])) {
                    $paymentRequest = $paymentRequest->withTrashed();
                }
                if (in_array(0, $requestInfo['status'])) {
                    $requestInfo['status'] = array_merge($requestInfo['status'], [8, 9]);
                    $requestInfo['status'] = array_unique($requestInfo['status']);
                    $paymentRequest = $paymentRequest->whereHas('approval', function ($query) use ($requestInfo) {
                        $query->whereIn('status', $requestInfo['status']);
                    });
                } else {
                    $paymentRequest = $paymentRequest->whereHas('approval', function ($query) use ($requestInfo) {
                        $query->whereIn('status', $requestInfo['status']);
                    });
                }
            } else if (!is_array($requestInfo['status'])) {
                if ($requestInfo['status'] == 3) {
                    $paymentRequest = $paymentRequest->withTrashed();
                }
                $paymentRequest = $paymentRequest->whereHas('approval', function ($query) use ($requestInfo) {
                    if ($requestInfo['status'] == 0) {
                        $query->whereIn('status', [0, 8, 9]);
                    } else {
                        $query->where('status', $requestInfo['status']);
                    }
                });
            }
        }

        if (array_key_exists('role', $requestInfo)) {
            $approvalFlowOrders = ApprovalFlow::where('role_id', $requestInfo['role'])->get(['order', 'group_approval_flow_id']);
            $paymentRequestIDs = [];
            /*$approvalFlowOrders = DB::select(
                'SELECT *
                FROM api.approval_flow
                WHERE role_id = ' . $requestInfo['role'] . '
                GROUP BY
                approval_flow.order,
                group_approval_flow_id
                order by id ASC'
            );*/
            foreach ($approvalFlowOrders as $approvalFlowOrder) {
                $accountApprovalFlow = AccountsPayableApprovalFlowClean::where('order', $approvalFlowOrder['order'])->with('payment_request');
                $accountApprovalFlow = $accountApprovalFlow->whereHas('payment_request', function ($query) use ($approvalFlowOrder) {
                    $query->where('group_approval_flow_id', $approvalFlowOrder['group_approval_flow_id']);
                })->whereIn('status', [0, 2, 8, 9])->get('payment_request_id');
                $paymentRequestIDs = array_merge($paymentRequestIDs, $accountApprovalFlow->pluck('payment_request_id')->toArray());
            }
            $paymentRequest = $paymentRequest->whereIn('id', $paymentRequestIDs);
        }

        if (array_key_exists('created_at', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['created_at'])) {
                $paymentRequest->where('created_at', '>=', $requestInfo['created_at']['from']);
            }
            if (array_key_exists('to', $requestInfo['created_at'])) {
                $paymentRequest->where('created_at', '<=', date("Y-m-d", strtotime("+1 days", strtotime($requestInfo['created_at']['to']))));
            }
            if (!array_key_exists('to', $requestInfo['created_at']) && !array_key_exists('from', $requestInfo['created_at'])) {
                $paymentRequest->whereBetween('created_at', [now()->addMonths(-1), now()]);
            }
        }
        if (array_key_exists('pay_date', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['pay_date'])) {
                $paymentRequest->where('pay_date', '>=', $requestInfo['pay_date']['from']);
            }
            if (array_key_exists('to', $requestInfo['pay_date'])) {
                $paymentRequest->where('pay_date', '<=', $requestInfo['pay_date']['to']);
            }
            if (!array_key_exists('to', $requestInfo['pay_date']) && !array_key_exists('from', $requestInfo['pay_date'])) {
                $paymentRequest->whereBetween('pay_date', [now(), now()->addMonths(1)]);
            }
        }
        if (array_key_exists('extension_date', $requestInfo)) {
            if (!$installment) {
                $installments = DB::select("SELECT id as id_payment_request, (select
            id as id_payment_requests_installments
            FROM api.payment_requests_installments
            WHERE payment_request_id = id_payment_request
            AND status <> 4
            AND status <> 7
            ORDER BY extension_date asc
            LIMIT 1) AS id_installment
            FROM api.payment_requests");

                $installmentIDs = [];

                foreach ($installments as $installment) {
                    if ($installment->id_installment != null) {
                        array_push($installmentIDs, $installment->id_installment);
                    }
                }
                $requestInfo['installmentsIds'] = $installmentIDs;

                $paymentRequest->whereHas('installments', function ($query) use ($requestInfo) {
                    $query->whereIn('id', $requestInfo['installmentsIds']);
                    if (array_key_exists('from', $requestInfo['extension_date'])) {
                        $query->where('extension_date', '>=', $requestInfo['extension_date']['from']);
                    }
                    if (array_key_exists('to', $requestInfo['extension_date'])) {
                        $query->where('extension_date', '<=', $requestInfo['extension_date']['to']);
                    }
                    if (!array_key_exists('to', $requestInfo['extension_date']) && !array_key_exists('from', $requestInfo['extension_date'])) {
                        $query->whereBetween('extension_date', [now(), now()->addMonths(1)]);
                    }
                });
            }
        }

        if (array_key_exists('cnab_date', $requestInfo)) {
            $paymentRequest->whereHas('cnab_payment_request', function ($cnabPaymentRequest) use ($requestInfo) {
                $cnabPaymentRequest->whereHas('cnab_generated', function ($cnabGenerated) use ($requestInfo) {
                    if (array_key_exists('from', $requestInfo['cnab_date'])) {
                        $cnabGenerated->where('file_date', '>=', $requestInfo['cnab_date']['from']);
                    }
                    if (array_key_exists('to', $requestInfo['cnab_date'])) {
                        $cnabGenerated->where('file_date', '<=', $requestInfo['cnab_date']['to']);
                    }
                    if (!array_key_exists('to', $requestInfo['cnab_date']) && !array_key_exists('from', $requestInfo['cnab_date'])) {
                        $cnabGenerated->whereBetween('file_date', [now(), now()->addMonths(1)]);
                    }
                });
            });
        }

        if (array_key_exists('installment_linked', $requestInfo)) {
            $paymentRequest = $paymentRequest->withoutGlobalScopes();
        }

        if (array_key_exists('card_identifier', $requestInfo)) {
            $paymentRequest = $paymentRequest->whereHas('approval', function ($query) use ($requestInfo) {
                $query->whereLike('card_identifier', "%{$requestInfo['card_identifier']}%");
            });
        }

        if (array_key_exists('approval_date', $requestInfo)) {
            $validPaymentRequestsIds = PaymentRequestClean::with(['log_approval_flow', 'approval'])
                ->whereHas('approval', fn ($q) => $q->where('status', Config::get('constants.status.approved')))
                ->get()
                ->filter(self::approvalDatePaymentRequestFilter($requestInfo['approval_date'], true));

            if (isset($validPaymentRequestsIds)) {
                $paymentRequest = $paymentRequest->whereIn('id', $validPaymentRequestsIds->pluck('id'));
            }
        }
        return $paymentRequest;
    }

    public static function approvalDatePaymentRequestFilter($dateInfo, $appendTimestamp = true)
    {
        $from = null;
        $to = null;

        if (array_key_exists('from', $dateInfo) && isset($dateInfo['from']))
            $from = $dateInfo['from'] . ($appendTimestamp ? ' 00:00:00' : '');

        if (array_key_exists('to', $dateInfo) && isset($dateInfo['to']))
            $to = $dateInfo['to'] . ($appendTimestamp ? ' 23:59:59' : '');

        return function ($payment) use ($from, $to) {
            $lastApproval = $payment->log_approval_flow
                ->where('id', $payment->log_approval_flow->max('id'))
                ->first();

            // XXX: is this the best approach in this case?
            if (is_null($lastApproval))
                return false;

            $passes = $lastApproval->type == 'approved';

            if (isset($from))
                $passes = $passes && $lastApproval->created_at >= $from;

            if (isset($to))
                $passes = $passes && $lastApproval->created_at <= $to;

            return $passes;
        };
    }

    public static function createLogApprovalFlowLogPaymentRequest($paymentRequestID, $type, $motive, $description, $stage, $userID, $recipient, $createdAt = null, $currentOrder = null)
    {
        $user = User::withTrashed()->with('role')->find($userID);
        $temporary = $user->logged_user_id == null ? false : true;
        $role = Role::withTrashed()->find(auth()->user()->role_id);
        if ($createdAt == null) {
            AccountsPayableApprovalFlowLog::create(
                [
                    'type' => $type,
                    'motive' => $motive,
                    'description' => $description,
                    'stage' => $stage,
                    'user_id' => $user != null ?  $user->id : null,
                    'user_name' => $user != null ? $user->name : null,
                    'user_role' => $role != null ? $role->title : null,
                    'payment_request_id' => $paymentRequestID,
                    'recipient' => $recipient,
                    'temporary' => $temporary,
                    'current_stage' => $currentOrder,
                ]
            );
        } else {
            AccountsPayableApprovalFlowLog::create(
                [
                    'type' => $type,
                    'motive' => $motive,
                    'description' => $description,
                    'stage' => $stage,
                    'user_id' => $user != null ?  $user->id : null,
                    'user_name' => $user != null ? $user->name : null,
                    'user_role' => $role != null ? $role : null,
                    'payment_request_id' => $paymentRequestID,
                    'recipient' => $recipient,
                    'created_at' => $createdAt,
                    'temporary' => $temporary,
                    'current_stage' => $currentOrder,
                ]
            );
        }
    }

    public static function createBillingLog($billingId, $type, $motive, $description, $stage, $userID, $createdAt = null)
    {
        $user = User::withTrashed()->with('role')->find($userID);
        if ($createdAt == null) {
            BillingLog::create(
                [
                    'type' => $type,
                    'motive' => $motive,
                    'description' => $description,
                    'stage' => $stage,
                    'user_id' => $user != null ?  $user->id : null,
                    'user_name' => $user != null ? $user->name : null,
                    'user_role' => $user != null ? ($user->role != null ? $user->role->title : null) : null,
                    'billing_id' => $billingId,
                ]
            );
        } else {
            BillingLog::create(
                [
                    'type' => $type,
                    'motive' => $motive,
                    'description' => $description,
                    'stage' => $stage,
                    'user_id' => $user != null ?  $user->id : null,
                    'user_name' => $user != null ? $user->name : null,
                    'user_role' => $user != null ? ($user->role != null ? $user->role->title : null) : null,
                    'billing_id' => $billingId,
                    'created_at' => $createdAt
                ]
            );
        }
    }

    public static function createHotelLog($hotelId, $type, $motive, $description, $stage, $userID, $createdAt = null)
    {
        $user = User::withTrashed()->with('role')->find($userID);
        if ($createdAt == null) {
            HotelLog::create(
                [
                    'type' => $type,
                    'motive' => $motive,
                    'description' => $description,
                    'stage' => $stage,
                    'user_id' => $user != null ?  $user->id : null,
                    'user_name' => $user != null ? $user->name : null,
                    'user_role' => $user != null ? ($user->role != null ? $user->role->title : null) : null,
                    'hotel_id' => $hotelId,
                ]
            );
        } else {
            HotelLog::create(
                [
                    'type' => $type,
                    'motive' => $motive,
                    'description' => $description,
                    'stage' => $stage,
                    'user_id' => $user != null ?  $user->id : null,
                    'user_name' => $user != null ? $user->name : null,
                    'user_role' => $user != null ? ($user->role != null ? $user->role->title : null) : null,
                    'hotel_id' => $hotelId,
                    'created_at' => $createdAt
                ]
            );
        }
    }

    public static function baseFilterPurchaseOrder($purchaseOrder, $requestInfo)
    {

        if (array_key_exists('provider', $requestInfo)) {
            $purchaseOrder->whereHas('provider', function ($query) use ($requestInfo) {
                $query->where('provider_id', $requestInfo['provider']);
            });
        }

        if (array_key_exists('cost_center', $requestInfo)) {
            $purchaseOrder->whereHas('cost_centers', function ($query) use ($requestInfo) {
                $query->where('cost_center_id', $requestInfo['cost_center']);
            });
        }

        if (array_key_exists('service', $requestInfo)) {
            $purchaseOrder->whereHas('services', function ($query) use ($requestInfo) {
                $query->where('service_id', $requestInfo['service']);
            });
        }

        if (array_key_exists('product', $requestInfo)) {
            $purchaseOrder->whereHas('products', function ($query) use ($requestInfo) {
                $query->where('product_id', $requestInfo['product']);
            });
        }

        if (array_key_exists('status', $requestInfo)) {
            $deliveryIds = [];
            foreach (PurchaseOrderDelivery::where('status', $requestInfo['status'])->get() as $getPurchaseOrderId) {
                if (!in_array($getPurchaseOrderId->purchase_order_id, $deliveryIds)) {
                    $deliveryIds[] = $getPurchaseOrderId->purchase_order_id;
                }
            }
            $purchaseOrder->whereIn('id', $deliveryIds);
        }

        if (array_key_exists('billing_date', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['billing_date'])) {
                $purchaseOrder->where('billing_date', '>=', $requestInfo['billing_date']['from']);
            }
            if (array_key_exists('to', $requestInfo['billing_date'])) {
                $purchaseOrder->where('billing_date', '<=', $requestInfo['billing_date']['to']);
            }
        }

        //New
        if (array_key_exists('negotiated_total_value_from', $requestInfo)) {
            $purchaseOrder = $purchaseOrder->where('negotiated_total_value', '>=', $requestInfo['negotiated_total_value_from']);
        }

        if (array_key_exists('negotiated_total_value_to', $requestInfo)) {
            $purchaseOrder = $purchaseOrder->where('negotiated_total_value', '<=', $requestInfo['negotiated_total_value_to']);
        }

        if (array_key_exists('cpfcnpj', $requestInfo)) {
            $purchaseOrder = $purchaseOrder->whereHas('provider', function ($query) use ($requestInfo) {
                $query->where('cpf', $requestInfo['cpfcnpj'])->orWhere('cnpj', $requestInfo['cpfcnpj']);
            });
        }

        if (array_key_exists('company', $requestInfo)) {
            $purchaseOrder = $purchaseOrder->where('company_id', $requestInfo['company']);
        }

        if (array_key_exists('created_at', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['created_at'])) {
                $purchaseOrder = $purchaseOrder->whereDate('created_at', '>=', $requestInfo['created_at']['from']);
            }
            if (array_key_exists('to', $requestInfo['created_at'])) {
                $purchaseOrder = $purchaseOrder->whereDate('created_at', '<=', $requestInfo['created_at']['to']);
            }
            if (!array_key_exists('to', $requestInfo['created_at']) && !array_key_exists('from', $requestInfo['created_at'])) {
                $purchaseOrder = $purchaseOrder->whereBetween('created_at', [now()->addMonths(-1), now()]);
            }
        }

        if (array_key_exists('role', $requestInfo)) {
            $approvalFlowOrders = ApprovalFlowSupply::where('role_id', $requestInfo['role'])->get(['order']);

            $purchaseOrderIDs = [];
            foreach ($approvalFlowOrders as $approvalFlowOrder) {
                $supplyApprovalFlow = SupplyApprovalFlow::where('order', $approvalFlowOrder['order'])->get();
                $purchaseOrderIDs = array_merge($purchaseOrderIDs, $supplyApprovalFlow->pluck('id_purchase_order')->toArray());
            }
            $purchaseOrder = $purchaseOrder->whereIn('id', $purchaseOrderIDs);
        }

        if (array_key_exists('user', $requestInfo)) {
            $purchaseOrder = $purchaseOrder->where('user_id', $requestInfo['user']);
        }

        if (array_key_exists('purchase_order', $requestInfo)) {
            $purchaseOrder = $purchaseOrder->where('id', $requestInfo['purchase_order']);
        }

        if (array_key_exists('chart_of_accounts', $requestInfo)) {

            $getPurchaseOrder = new PurchaseOrder();
            $purchaseOrderIDs = [];

            $getPurchaseOrderHasProductsIDs = $getPurchaseOrder->whereHas('products', function ($query) use ($requestInfo) {
                $query->whereHas('product',  function ($query2) use ($requestInfo) {
                    $query2->where('chart_of_accounts_id', $requestInfo['chart_of_accounts']);
                });
            })->get('id');

            if ($getPurchaseOrderHasProductsIDs->isNotEmpty()) {
                $purchaseOrderIDs = array_merge($purchaseOrderIDs, $getPurchaseOrderHasProductsIDs->pluck('id')->toArray());
            }

            $getPurchaseOrderHasServicesIDs = $getPurchaseOrder->whereHas('services', function ($query3) use ($requestInfo) {
                $query3->whereHas('service',  function ($query4) use ($requestInfo) {
                    $query4->where('chart_of_accounts_id', $requestInfo['chart_of_accounts']);
                });
            })->get('id');

            if ($getPurchaseOrderHasServicesIDs->isNotEmpty()) {
                $purchaseOrderIDs = array_merge($purchaseOrderIDs, $getPurchaseOrderHasServicesIDs->pluck('id')->toArray());
            }

            $purchaseOrder->whereIn('id', $purchaseOrderIDs);
        }

        if (array_key_exists('approved', $requestInfo)) {
            if ($requestInfo['approved'] == "true") {
                $purchaseOrder = $purchaseOrder->whereHas('approval', function ($query) use ($requestInfo) {
                    $query->where('status', 1);
                });
            }
        }
        if (array_key_exists('reproved', $requestInfo)) {
            if ($requestInfo['reproved'] == "true") {
                $purchaseOrder = $purchaseOrder->whereHas('approval', function ($query) use ($requestInfo) {
                    $query->where('status', 2);
                });
            }
        }
        if (array_key_exists('deleted', $requestInfo)) {
            if ($requestInfo['deleted'] == "true") {
                $purchaseOrder = $purchaseOrder->withTrashed()->where('deleted_at', '!=', null);
            }
        }

        if (array_key_exists('approver_stage_people', $requestInfo)) {

            $getPurchaseOrderApprover = new PurchaseOrder();
            $purchaseOrderListIDs = [];
            $getUserName = User::where('id', $requestInfo['approver_stage_people'])->pluck('name')->firstOrFail();
            $purchases = $getPurchaseOrderApprover->get();

            foreach ($purchases as $purchase) {
                foreach ($purchase->approver_stage as $purchaseStage) {
                    if (in_array($getUserName, $purchaseStage['names'])) {
                        $purchaseOrderListIDs[] = $purchase->id;
                    }
                }
            }

            $purchaseOrder->whereIn('id', $purchaseOrderListIDs);
        }

        return $purchaseOrder;
    }

    public static function baseFilterReportsInstallment($installment, $requestInfo)
    {
        if (array_key_exists('net_value', $requestInfo)) {
            $installment = $installment->where('initial_value', $requestInfo['net_value']);
        }
        if (array_key_exists('extension_date', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['extension_date'])) {
                $installment->where('extension_date', '>=', $requestInfo['extension_date']['from']);
            }
            if (array_key_exists('to', $requestInfo['extension_date'])) {
                $installment->where('extension_date', '<=', $requestInfo['extension_date']['to']);
            }
            if (!array_key_exists('to', $requestInfo['extension_date']) && !array_key_exists('from', $requestInfo['extension_date'])) {
                $installment->whereBetween('extension_date', [now(), now()->addMonths(1)]);
            }
        }

        if (array_key_exists('installment_linked', $requestInfo)) {
            $installment = $installment->where('linked', false);
        }
        return $installment;
    }

    public static function baseFilterBilling($billing, $requestInfo)
    {
        if (array_key_exists('payment_status', $requestInfo)) {
            $billing->where('payment_status', $requestInfo['payment_status']);
        }
        if (array_key_exists('status_123', $requestInfo)) {
            $billing->where('status_123', $requestInfo['status_123']);
        }
        if (array_key_exists('status_cangooroo', $requestInfo)) {
            $billing->whereHas('cangooroo', function ($query) use ($requestInfo) {
                $query->where('status', $requestInfo['status_cangooroo']);
            });
        }
        if (array_key_exists('id_hotel_cangooroo', $requestInfo)) {
            $billing->whereHas('cangooroo', function ($query) use ($requestInfo) {
                $query->where('hotel_id', $requestInfo['id_hotel_cangooroo']);
            });
        }
        if (array_key_exists('created_at', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['created_at'])) {
                $billing->where('created_at', '>=', $requestInfo['created_at']['from']);
            }
            if (array_key_exists('to', $requestInfo['created_at'])) {
                $billing->where('created_at', '<=', date("Y-m-d", strtotime("+1 days", strtotime($requestInfo['created_at']['to']))));
            }
            if (!array_key_exists('to', $requestInfo['created_at']) && !array_key_exists('from', $requestInfo['created_at'])) {
                $billing->whereBetween('created_at', [now()->addMonths(-1), now()]);
            }
        }
        if (array_key_exists('updated_at', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['updated_at'])) {
                $billing->where('updated_at', '>=', $requestInfo['updated_at']['from']);
            }
            if (array_key_exists('to', $requestInfo['updated_at'])) {
                $billing->where('updated_at', '<=', date("Y-m-d", strtotime("+1 days", strtotime($requestInfo['updated_at']['to']))));
            }
            if (!array_key_exists('to', $requestInfo['updated_at']) && !array_key_exists('from', $requestInfo['updated_at'])) {
                $billing->whereBetween('updated_at', [now()->addMonths(-1), now()]);
            }
        }
        if (array_key_exists('pay_date', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['pay_date'])) {
                $billing->where('pay_date', '>=', $requestInfo['pay_date']['from']);
            }
            if (array_key_exists('to', $requestInfo['pay_date'])) {
                $billing->where('pay_date', '<=', $requestInfo['pay_date']['to']);
            }
            if (!array_key_exists('to', $requestInfo['pay_date']) && !array_key_exists('from', $requestInfo['pay_date'])) {
                $billing->whereBetween('pay_date', [now(), now()->addMonths(1)]);
            }
        }
        if (array_key_exists('pax_in_house', $requestInfo)) {
            $billing->where('pax_in_house', $requestInfo['pax_in_house']);
        }
        if (array_key_exists('suggestion', $requestInfo)) {
            $billing->where('suggestion', $requestInfo['suggestion']);
        }
        if (array_key_exists('form_of_payment', $requestInfo)) {
            $billing->where('form_of_payment', $requestInfo['form_of_payment']);
        }
        if (array_key_exists('role', $requestInfo)) {
            $approvalFlowOrders = HotelApprovalFlow::where('role_id', $requestInfo['role'])->get(['order']);
            $billingIDs = [];
            foreach ($approvalFlowOrders as $approvalFlowOrder) {
                $billingApprovalFlow = Billing::where('order', $approvalFlowOrder['order']);
                $billingIDs = array_merge($billingIDs, $billingApprovalFlow->pluck('id')->toArray());
            }
            $billing = $billing->whereIn('id', $billingIDs);
        }
        if (array_key_exists('order', $requestInfo)) {
            $billing = Billing::where('order', $requestInfo['order']);
        }
        if (array_key_exists('cnpj', $requestInfo)) {
            $billing->where('cnpj', $requestInfo['cnpj']);
        }
        if (array_key_exists('user_id', $requestInfo)) {
            $billing->where('user_id', $requestInfo['user_id']);
        }
        if (array_key_exists('client_name', $requestInfo)) {
            $billing->whereHas('cangooroo', function ($query) use ($requestInfo) {
                $query->where('client_name', $requestInfo['client_name']);
            });
        }
        if (array_key_exists('reserve', $requestInfo) && !is_null($requestInfo['reserve']) && count($requestInfo['reserve']) > 0){
            $billing->whereIn('reserve', $requestInfo['reserve']);
        }
        return $billing;
    }

    public static function baseFilterPaidBillingInfo($paidBillingInfo, $requestInfo)
    {
        if (array_key_exists('created_at', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['created_at'])) {
                $paidBillingInfo->where('created_at', '>=', $requestInfo['created_at']['from']);
            }
            if (array_key_exists('to', $requestInfo['created_at'])) {
                $paidBillingInfo->where('created_at', '<=', date("Y-m-d", strtotime("+1 days", strtotime($requestInfo['created_at']['to']))));
            }
            if (!array_key_exists('to', $requestInfo['created_at']) && !array_key_exists('from', $requestInfo['created_at'])) {
                $paidBillingInfo->whereBetween('created_at', [now()->addMonths(-1), now()]);
            }
        }
        if (array_key_exists('pay_date', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['pay_date'])) {
                $paidBillingInfo->where('pay_date', '>=', $requestInfo['pay_date']['from']);
            }
            if (array_key_exists('to', $requestInfo['pay_date'])) {
                $paidBillingInfo->where('pay_date', '<=', $requestInfo['pay_date']['to']);
            }
            if (!array_key_exists('to', $requestInfo['pay_date']) && !array_key_exists('from', $requestInfo['pay_date'])) {
                $paidBillingInfo->whereBetween('pay_date', [now(), now()->addMonths(1)]);
            }
        }
        if (array_key_exists('form_of_payment', $requestInfo)) {
            $paidBillingInfo->where('form_of_payment', $requestInfo['form_of_payment']);
        }
        if (array_key_exists('cnpj', $requestInfo)) {
            $paidBillingInfo->where('cnpj_hotel', $requestInfo['cnpj']);
        }
        if (array_key_exists('service_id', $requestInfo)) {
            $paidBillingInfo->where('service_id', $requestInfo['service_id']);
        }
        if (array_key_exists('reserve', $requestInfo)) {
            $paidBillingInfo->where('reserve', $requestInfo['reserve']);
        }
        if (array_key_exists('client_name', $requestInfo)) {
            $paidBillingInfo->where('client_name', $requestInfo['client_name']);
        }
        return $paidBillingInfo;
    }

    public static function baseFilterGroupFormPayment($groupFormPayment, $requestInfo)
    {
        if (array_key_exists('only_not_main_payments', $requestInfo) && ($requestInfo['only_not_main_payments'] == 1 || $requestInfo['only_not_main_payments'] == '1')) {
            //$groupFormPayment->where('main_payment', '=', '0');
        }

        return $groupFormPayment;
    }

    public static function groupBillings($billings, $bankCode)
    {

        $groupBilling = [];
        foreach ($billings as $billing) {
            if ($billing->form_of_payment == 1) //Default PIX group 2
            {
                if (array_key_exists('45', $groupBilling)) {
                    array_push($groupBilling['45'], $billing);
                } else {
                    $groupBilling['45'] = [$billing];
                }
            } elseif ($billing->form_of_payment == 0) {
                if (substr($billing->boleto_code, 0, 3) == $bankCode) {
                    if (array_key_exists('30', $groupBilling)) {
                        array_push($groupBilling['30'], $billing);
                    } else {
                        $groupBilling['30'] = [$billing];
                    }
                } else {
                    if (array_key_exists('31', $groupBilling)) {
                        array_push($groupBilling['31'], $billing);
                    } else {
                        $groupBilling['31'] = [$billing];
                    }
                }
            }
        }
        return $groupBilling;
    }
    public static function createManualLog($old, $new, $causerID, $model, $logName, $log = 'updated')
    {
        activity()
            ->causedBy(User::findOrFail($causerID))
            ->performedOn($model)
            ->withProperties([
                'old' => $old,
                'attributes' => $new,
            ])
            ->tap(function (Activity $activity) use ($causerID, $new, $logName) {
                $user = User::with(['cost_center', 'business', 'role'])->findOrFail($causerID);
                $user->role = Role::findOrFail($user->role_id);
                $activity->causer_id = $user->id;
                $activity->causer_object = $user;
                $activity->subject_id = $new->id;
                $activity->log_name = $logName;
            })
            ->log($log);
    }

    public static function createPaiBillingInfo($billings)
    {
        foreach ($billings as $key => $billing) {
            $bankAccount = $billing->bank_account;
            $cangooroo = $billing->cangooroo;
            $data =
                [
                    'reserve' => $billing['reserve'],
                    'operator' => !is_null($billing->user) ? $billing->user->name : '',
                    'supplier_value' => $billing['supplier_value'],
                    'pay_date' => $billing['pay_date'],
                    'boleto_value' => $billing['boleto_value'],
                    'boleto_code' => $billing['boleto_code'],
                    'remark' => $billing['remark'],
                    'oracle_protocol' => $billing['oracle_protocol'],
                    'user_id' => !is_null($billing->user) ? $billing->user->id : '',
                    'bank' => !is_null($bankAccount) ? (!is_null($bankAccount->bank) ? $bankAccount->bank->title : '') : '',
                    'bank_code' => !is_null($bankAccount) ? (!is_null($bankAccount->bank) ? $bankAccount->bank->bank_code : '') : '',
                    'agency' => !is_null($bankAccount) ? (!!($bankAccount->agency_check_number) || $bankAccount->agency_check_number === '0' ? $bankAccount->agency_number . '-' . $bankAccount->agency_check_number : $bankAccount->agency_number) : '',
                    'account' => !is_null($bankAccount) ? (!!($bankAccount->account_check_number) || $bankAccount->account_check_number === '0' ? $bankAccount->account_number . '-' . $bankAccount->account_check_number : $bankAccount->account_number) : '',
                    'form_of_payment' => !is_null($billing->form_of_payment) ? $billing->formsOfPayment[$billing->form_of_payment] : '',
                    'hotel_name' => !is_null($cangooroo) ? $cangooroo->hotel_name : '',
                    'client_name' => !is_null($cangooroo) ? $cangooroo->client_name : '',
                    'cnpj_hotel' => $billing['cnpj'],
                    'payment_voucher' => '',
                    'payment_method' => !is_null($billing->form_of_payment) ? $billing->formsOfPayment[$billing->form_of_payment] : '',
                    'payment_bank' => '',
                    'payment_remark' => '',
                    'created_at' => $billing->created_at,
                ];
            $paidBillingInfo = new PaidBillingInfo();
            $paidBillingInfo = $paidBillingInfo->create($data);
        }
    }

    public static function statusApprovalFlowRequest($requestInfo)
    {
        $arrayStatus = [];
        if (array_key_exists('status', $requestInfo)) {
            if (is_array($requestInfo['status']) && !empty($requestInfo['status'])) {
                if (in_array(0, $requestInfo['status'])) {
                    array_push($arrayStatus, 0);
                }
                if (in_array(2, $requestInfo['status'])) {
                    array_push($arrayStatus, 2);
                }
            } else {
                array_push($arrayStatus, 0, 2);
            }
        } else {
            array_push($arrayStatus, 0, 2);
        }

        return $arrayStatus;
    }

    public static function userWithActiveNotification($type, $schedule)
    {
        $notificationId = NotificationCatalog::where(['type' => $type, 'active' => true, 'schedule' => $schedule])->firstOrFail(['id', 'type']);
        $usersId = [];
        $usersId = NotificationCatalogHasUsers::where('notification_catalog_id', $notificationId->id)->with('user')->get('user_id')->pluck('user_id')->toArray();

        foreach (NotificationCatalogHasRoles::where('notification_catalog_id', $notificationId->id)->with('user')->get() as $roleUsers) {
            foreach ($roleUsers->user as $user) {
                $usersId[] = $user->id;
            }
        }

        return array_unique($usersId);
    }

    public static function paiOutInstallmentLinked($installmentId)
    {
        $installment = PaymentRequestHasInstallments::with('payment_request.approval')->findOrFail($installmentId);
        if ($installment->payment_request->approval->status == Config::get('constants.status.paid out')) {
            if (PaymentRequestHasInstallmentLinked::where('payment_request_id', $installment->payment_request_id)->exists()) {
                $installmentLinked = PaymentRequestHasInstallmentLinked::where('payment_request_id', $installment->payment_request_id)->get('payment_requests_installment_id');
                DB::table('payment_requests_installments')->whereIn('id', $installmentLinked->pluck('payment_requests_installment_id')->toArray())->update(['status' => Config::get('constants.status.paid out')]);
            }
        }
    }

    public static function replaceCharacterUpload($string)
    {
        $normalizeChars = array(
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Å' => 'A', 'Ä' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ð' => 'Eth',
            'Ñ' => 'N', 'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Ŕ' => 'R',

            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a', 'ä' => 'a', 'æ' => 'ae', 'ç' => 'c',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'eth',
            'ñ' => 'n', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ŕ' => 'r', 'ÿ' => 'y',

            'ß' => 'sz', 'þ' => 'thorn', 'º' => '', 'ª' => '', '°' => '',
        );

        return preg_replace('/[^0-9a-zA-Z *\-\\[\]\{\}\/\\_]/', '', strtr($string, $normalizeChars));
    }

    public static function getHolidayList($ano = null)
    {

        if ($ano === null) {
            $ano = intval(date('Y'));
        }

        $pascoa = easter_date($ano); // retorna data da pascoa do ano especificado
        $diaPascoa = date('j', $pascoa);
        $mesPacoa = date('n', $pascoa);
        $anoPascoa = date('Y', $pascoa);

        $feriados = [
            // Feriados nacionais fixos
            mktime(0, 0, 0, 1, 1, $ano),   // Confraternização Universal
            mktime(0, 0, 0, 4, 21, $ano),  // Tiradentes
            mktime(0, 0, 0, 5, 1, $ano),   // Dia do Trabalhador
            mktime(0, 0, 0, 9, 7, $ano),   // Dia da Independência
            mktime(0, 0, 0, 10, 12, $ano), // N. S. Aparecida
            mktime(0, 0, 0, 11, 2, $ano),  // Todos os santos
            mktime(0, 0, 0, 11, 15, $ano), // Proclamação da republica
            mktime(0, 0, 0, 12, 25, $ano), // Natal
            //
            // Feriados variaveis
            mktime(0, 0, 0, $mesPacoa, $diaPascoa - 48, $anoPascoa), // 2º feria Carnaval
            mktime(0, 0, 0, $mesPacoa, $diaPascoa - 47, $anoPascoa), // 3º feria Carnaval
            mktime(0, 0, 0, $mesPacoa, $diaPascoa - 2, $anoPascoa),  // 6º feira Santa
            mktime(0, 0, 0, $mesPacoa, $diaPascoa, $anoPascoa),      // Pascoa
            mktime(0, 0, 0, $mesPacoa, $diaPascoa + 60, $anoPascoa), // Corpus Christ
        ];

        sort($feriados);

        $listaDiasFeriado = [];
        foreach ($feriados as $feriado) {
            $data = date('Y-m-d', $feriado);
            $listaDiasFeriado[$data] = $data;
        }

        return $listaDiasFeriado;
    }

    public static function isHoliday($data)
    {
        $holidayList = self::getHolidayList(date('Y', strtotime($data)));
        if (isset($holidayList[$data])) {
            return true;
        }

        return false;
    }

    public static function getUtileDays($from, $daysQuantity = 30, $reverse = false)
    {
        $dateTime = new DateTime($from);

        $utilesDayList = [];
        $count = 0;
        $sumWeekday = $reverse ? '-1 weekday' : '+1 weekday';
        while ($count < $daysQuantity) {
            $dateTime->modify($sumWeekday);
            $data = $dateTime->format('Y-m-d');
            if (!self::isHoliday($data)) {
                $utilesDayList[] = $data;
                $count++;
            }
        }

        return $utilesDayList;
    }

    public static function getLastUtileDay($from, $daysQuantity = 30, $reverse = false)
    {
        $list = self::getUtileDays($from, $daysQuantity, $reverse);
        return end($list);
    }

    public static function getBankDetailsCnab($installment)
    {
        $nomeBeneficiario = '';
        $inscricao = '';

        if ($installment->bank_account_provider == null) {
            if ($installment->client_identifier != null && !empty($installment->client_identifier)) {
                $inscricao = self::onlyNumbers($installment->client_identifier);
                $nomeBeneficiario = $installment->client_name;
            } else {
                $inscricao = $installment->payment_request->provider->provider_type == 'J' ? self::onlyNumbers($installment->payment_request->provider->cnpj) : self::onlyNumbers($installment->payment_request->provider->cpf);
                $nomeBeneficiario = $installment->payment_request->provider->provider_type == 'J' ? $installment->payment_request->provider->company_name : $installment->payment_request->provider->full_name;
            }
        } else {
            if ($installment->bank_account_provider->entity_name == null) {
                $inscricao = $installment->payment_request->provider->provider_type == 'J' ? self::onlyNumbers($installment->payment_request->provider->cnpj) : self::onlyNumbers($installment->payment_request->provider->cpf);
                $nomeBeneficiario = $installment->payment_request->provider->provider_type == 'J' ? $installment->payment_request->provider->company_name : $installment->payment_request->provider->full_name;
            } else {
                $inscricao = $installment->bank_account_provider->cpf_cnpj == null ? ($installment->payment_request->provider->provider_type == 'J' ? self::onlyNumbers($installment->payment_request->provider->cnpj) : self::onlyNumbers($installment->payment_request->provider->cpf)) : self::onlyNumbers($installment->bank_account_provider->cpf_cnpj);
                $nomeBeneficiario = $installment->bank_account_provider->entity_name;
            }
        }

        return [
            'nomeBeneficiario' => $nomeBeneficiario,
            'inscricao' => $inscricao
        ];
    }

    public static function typeKeyPix($bankAccount)
    {
        switch ($bankAccount->pix_key_type) {
            case 0:
            case 1:
                return '03';
                break;
            case 2:
                return '02';
                break;
            case 3:
                return '01';
                break;
            case 4:
                return '04';
                break;
            default:
                '00';
        }
    }

    public static function formatPixKey($bankAccount)
    {
        switch ($bankAccount->pix_key_type) {
            case 0:
            case 1:
            case 3:
                return Utils::formatCnab('X', self::onlyNumbers($bankAccount->pix_key), '100');
                break;
            case 2:
            case 4:
                return Utils::formatCnab('X', $bankAccount->pix_key, '100');
                break;
            default:
                '';
        }
    }
}
