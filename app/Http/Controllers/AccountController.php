<?php

namespace App\Http\Controllers;

use App\Support\Phone;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()
            ->orders()
            ->with('items')
            ->latest('id')
            ->paginate(10);

        return view('account.index', ['user' => $request->user(), 'orders' => $orders]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', function (string $attribute, mixed $value, Closure $fail) {
                if (Phone::normalize((string) $value) === null) {
                    $fail('Укажите телефон в формате +7 900 000-00-00.');
                }
            }],
        ]);

        $data['phone'] = filled($data['phone'] ?? null) ? Phone::normalize($data['phone']) : null;

        $request->user()->update($data);

        return back()->with('status', 'Профиль сохранён.');
    }
}
