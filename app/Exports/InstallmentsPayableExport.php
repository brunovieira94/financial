<?php

namespace App\Exports;

use App\Models\PaymentRequestHasInstallments;
use Config;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InstallmentsPayableExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{
    private $requestInfo;
    private $filterCanceled = false;

    public function __construct($requestInfo)
    {
        $this->requestInfo = $requestInfo;
    }

    use Exportable;

    public function collection()
    {
        $query = PaymentRequestHasInstallments::query();
        $query = $query->with(['cnab_generated_installment', 'payment_request', 'group_payment', 'bank_account_provider']);
        $requestInfo = $this->requestInfo;


        $query->whereHas('payment_request', function ($query) use ($requestInfo) {
            if (array_key_exists('amount', $requestInfo)) {
                $query->where('amount', $requestInfo['amount']);
            }
            if (array_key_exists('net_value', $requestInfo)) {
                $query->where('net_value', $requestInfo['net_value']);
            }
            if (array_key_exists('cpfcnpj', $requestInfo)) {
                $query->whereHas('provider', function ($query) use ($requestInfo) {
                    $query->where('cpf', $requestInfo['cpfcnpj'])->orWhere('cnpj', $requestInfo['cpfcnpj']);
                });
            }
            if (array_key_exists('provider', $requestInfo)) {
                $query->whereHas('provider', function ($query) use ($requestInfo) {
                    $query->where('id', $requestInfo['provider']);
                });
            }
            if (array_key_exists('chart_of_accounts', $requestInfo)) {
                $query->whereHas('chart_of_accounts', function ($query) use ($requestInfo) {
                    $query->where('id', $requestInfo['chart_of_accounts']);
                });
            }
            if (array_key_exists('cost_center', $requestInfo)) {
                $query->whereHas('cost_center', function ($query) use ($requestInfo) {
                    $query->where('id', $requestInfo['cost_center']);
                });
            }
            if (array_key_exists('payment_request', $requestInfo)) {
                $query->where('id', $requestInfo['payment_request']);
            }
            if (array_key_exists('user', $requestInfo)) {
                $query->whereHas('user', function ($query) use ($requestInfo) {
                    $query->where('id', $requestInfo['user']);
                });
            }
            if (array_key_exists('status', $requestInfo)) {
                $query->whereHas('approval', function ($query) use ($requestInfo) {
                    $query->where('status', $requestInfo['status']);
                    if ($requestInfo['status'] == 3) {
                        $this->filterCanceled = true;
                    }
                });
            }
            if (array_key_exists('approval_order', $requestInfo)) {
                $query->whereHas('approval', function ($query) use ($requestInfo) {
                    $query->where('order', $requestInfo['approval_order']);
                });
            }
            if (array_key_exists('created_at', $requestInfo)) {
                if (array_key_exists('from', $requestInfo['created_at'])) {
                    $query->where('created_at', '>=', $requestInfo['created_at']['from']);
                }
                if (array_key_exists('to', $requestInfo['created_at'])) {
                    $query->where('created_at', '<=', date("Y-m-d", strtotime("+1 days", strtotime($requestInfo['created_at']['to']))));
                }
                if (!array_key_exists('to', $requestInfo['created_at']) && !array_key_exists('from', $requestInfo['created_at'])) {
                    $query->whereBetween('created_at', [now()->addMonths(-1), now()]);
                }
            }
            if (array_key_exists('pay_date', $requestInfo)) {
                if (array_key_exists('from', $requestInfo['pay_date'])) {
                    $query->where('pay_date', '>=', $requestInfo['pay_date']['from']);
                }
                if (array_key_exists('to', $requestInfo['pay_date'])) {
                    $query->where('pay_date', '<=', $requestInfo['pay_date']['to']);
                }
                if (!array_key_exists('to', $requestInfo['pay_date']) && !array_key_exists('from', $requestInfo['pay_date'])) {
                    $query->whereBetween('pay_date', [now(), now()->addMonths(1)]);
                }
            }
            if (array_key_exists('days_late', $requestInfo)) {
                $query->whereHas('installments', function ($query) use ($requestInfo) {
                    $query->where('status', '!=', Config::get('constants.status.paid out'))->orWhereNull('status')->whereDate("due_date", "<=", Carbon::now()->subDays($requestInfo['days_late']));
                });
            }

            if (array_key_exists('company', $requestInfo)) {
                $query->whereHas('company', function ($query) use ($requestInfo) {
                    $query->where('id', $requestInfo['company']);
                });
            }

            if ($this->filterCanceled) {
                $query->withTrashed();
                $query->where('deleted_at', '!=', NULL);
            }
        });

        if (array_key_exists('cnab_date', $requestInfo)) {
            $query->whereHas('cnab_generated_installment', function ($cnabInstallment) use ($requestInfo) {
                $cnabInstallment->whereHas('generated_cnab', function ($cnabGenerated) use ($requestInfo) {
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

        if (array_key_exists('extension_date', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['extension_date'])) {
                if (array_key_exists('from', $requestInfo['extension_date'])) {
                    $query->where('extension_date', '>=', $requestInfo['extension_date']['from']);
                }
                if (array_key_exists('to', $requestInfo['extension_date'])) {
                    $query->where('extension_date', '<=', $requestInfo['extension_date']['to']);
                }
                if (!array_key_exists('to', $requestInfo['extension_date']) && !array_key_exists('from', $requestInfo['extension_date'])) {
                    $query->whereBetween('extension_date', [now(), now()->addMonths(1)]);
                }
            }
        }

        return $query->get();
    }

    public function map($query): array
    {
        return [
            $query->payment_request->id,
            $query->parcel_number,
            $query->payment_request->provider->trade_name ?? '',
            $query->cost_center ? $query->cost_center->title : $query->cost_center,
            $query->due_date,
            $query->extension_date,
            $query->competence_date,
            $query->initial_value,
            $query->fees,
            $query->fine,
            $query->discount,
            $query->portion_amount,
        ];
    }

    public function headings(): array
    {
        return [
            'Conta',
            'Parcela',
            'Fornecedor',
            'Centro de Custo',
            'Data de Pagamento',
            'Data de Prorrogação',
            'Data de Competência',
            'Valor',
            'Juros',
            'Multa',
            'Desconto',
            'Valor Final',
        ];
    }
}
