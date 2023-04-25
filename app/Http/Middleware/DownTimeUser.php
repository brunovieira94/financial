<?php

namespace App\Http\Middleware;

use App\Models\DowntimeUser as ModelsDowntimeUser;
use Closure;
use DB;
use Illuminate\Http\Request;

class DownTimeUser
{
    public function handle(Request $request, Closure $next)
    {
        if (ModelsDowntimeUser::where('updated_at', '>=', now()->timezone('America/Sao_Paulo')->subHours(env('DOWNTIME_HOUR', 2))->format('Y-m-d H:i:s'))
            ->where('user_id', auth()->user()->id)->exists()
        ) {
            ModelsDowntimeUser::where('user_id', auth()->user()->id)->update([
                'updated_at' => now()
            ]);
            return $next($request);
        } else {
            return response()->json([
                'error' => 'Tempo de acesso expirado! Entre no sistema novamente.'
            ], 422);
        }
    }
}
