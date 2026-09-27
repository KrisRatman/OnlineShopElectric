<?php

namespace App\Http\Controllers;

use App\Services\YooKassa\DemoYooKassaClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * «Страница оплаты» демо-режима — вместо формы ЮKassa. Работает только при YOOKASSA_DEMO=true.
 */
class DemoPaymentController extends Controller
{
    public function show(string $payment): View
    {
        $data = $this->payment($payment);

        return view('demo-payment', [
            'payment' => $data,
            'amount' => DemoYooKassaClient::amount($data),
        ]);
    }

    public function complete(Request $request, string $payment): RedirectResponse
    {
        $data = $this->payment($payment);

        DemoYooKassaClient::complete($payment, $request->input('result') === 'success');

        // Как ЮKassa: после оплаты — обратно в магазин, на return_url.
        return redirect()->away($data['return_url']);
    }

    /** @return array<string, mixed> */
    private function payment(string $id): array
    {
        abort_unless(config('yookassa.demo'), 404);

        return DemoYooKassaClient::find($id) ?? abort(404);
    }
}
