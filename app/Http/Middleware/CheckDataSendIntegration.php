<?php

namespace App\Http\Middleware;

use Closure;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class CheckDataSendIntegration
{
    public function handle(Request $request, Closure $next)
    {
        $requestInfo = $request->all();
        $from = new DateTime('2023-03-01 00:00:00');
        $to = new DateTime('2023-03-15 23:59:59');

        if ($this->isValidDateRange($requestInfo, $from, $to)) {
            return $next($request);
        }

        return Response::json([
            'error' => 'É necessário informar o parâmetro de data autorizado, início: 01/03/2023 até 15/03/2023'
        ]);
    }

    private function isValidDateRange(array $requestInfo, DateTime $from, DateTime $to): bool
    {
        if (array_key_exists('date_from', $requestInfo) && array_key_exists('date_to', $requestInfo)) {
            $dateRequestFrom = new DateTime($requestInfo['date_from']);
            $dateRequestTo = new DateTime($requestInfo['date_to']);

            return $dateRequestFrom >= $from && $dateRequestTo <= $to;
        }

        return false;
    }
}
