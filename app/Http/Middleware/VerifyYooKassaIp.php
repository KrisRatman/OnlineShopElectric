<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Уведомления ЮKassa не подписаны, поэтому первый фильтр — адрес отправителя.
 * Второй, главный, — повторный запрос платежа в API (см. YooKassaWebhookController).
 */
class VerifyYooKassaIp
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('yookassa.verify_ip') && ! IpUtils::checkIp((string) $request->ip(), config('yookassa.allowed_ips'))) {
            Log::warning('ЮKassa: уведомление с чужого адреса', ['ip' => $request->ip()]);

            abort(403);
        }

        return $next($request);
    }
}
