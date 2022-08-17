<?php

namespace App\Services;

use App\Models\AccountsPayableApprovalFlow;
use App\Models\AccountsPayableApprovalFlowClean;
use App\Models\ApprovalFlow;
use Carbon\Carbon;
use Config;
use DB;

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
        return $model->orderBy($orderBy, $order)->paginate($perPage);
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
            foreach ($installment->group_payment->form_payment as $payment_form) {
                if ($payment_form->bank_code == $bankCode) {
                    if ($payment_form->group_form_payment_id == 2) //Default PIX group 2
                    {
                        if (array_key_exists('45', $groupInstallment)) {
                            array_push($groupInstallment[$payment_form->code_cnab], $installment);
                            break;
                        } else {
                            $groupInstallment['45'] = [$installment];
                            break;
                        }
                    } elseif ($payment_form->group_form_payment_id == 1) {
                        if (substr($installment->bar_code, 0, 3) == $bankCode) {
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
        return substr($linhaDigitavel, 0, 4) . substr($linhaDigitavel, 32, 15) . substr($linhaDigitavel, 4, 5) . substr($linhaDigitavel, 9, 6) . substr($linhaDigitavel, 16, 4) . substr($linhaDigitavel, 21, 10);
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

    public static function baseFilterReportsPaymentRequest($paymentRequest, $requestInfo)
    {
        if (array_key_exists('provider', $requestInfo)) {
            $paymentRequest = $paymentRequest->where('provider_id', $requestInfo['provider']);
        }
        if (array_key_exists('net_value', $requestInfo)) {
            $paymentRequest = $paymentRequest->where('net_value', $requestInfo['net_value']);
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
        if (array_key_exists('extension_date', $requestInfo)) {
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
        }
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
            $paymentRequest = $paymentRequest->whereHas('approval', function ($query) use ($requestInfo) {
                $query->where('status', $requestInfo['status']);
            });
        }

        if (array_key_exists('role', $requestInfo)) {
            //$approvalFlowOrders = ApprovalFlow::where('role_id', $requestInfo['role'])->get(['order', 'group_approval_flow_id']);
            $paymentRequestIDs = [];

            $approvalFlowOrders = DB::select(
                'SELECT *
                FROM api.approval_flow
                WHERE role_id = ' . $requestInfo['role'] . '
                GROUP BY
                approval_flow.order,
                group_approval_flow_id
                order by id ASC'
            );

            foreach ($approvalFlowOrders as $approvalFlowOrder) {
                $accountApprovalFlow = AccountsPayableApprovalFlow::where('order', $approvalFlowOrder->order)->with('payment_request');
                $accountApprovalFlow = $accountApprovalFlow->whereHas('payment_request', function ($query) use ($approvalFlowOrder) {
                    $query->where('group_approval_flow_id', $approvalFlowOrder->group_approval_flow_id);
                })->get('payment_request_id');
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
        return $paymentRequest;
    }
}
