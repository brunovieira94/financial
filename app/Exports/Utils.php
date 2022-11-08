<?php

namespace App\Exports;

class Utils
{
    public static function logFirstApprovalFinancialAnalyst($paymentRequest)
    {
        $namesDate = [];
        if ($paymentRequest->first_approval_financial_analyst != null) {
            $namesDate['user_name'] = $paymentRequest->first_approval_financial_analyst['user_name'];
            $namesDate['created_at'] = $paymentRequest->first_approval_financial_analyst['created_at'];
        } else {
            $namesDate['user_name'] = null;
            $namesDate['created_at'] = null;
        }
        return $namesDate;
    }
}
