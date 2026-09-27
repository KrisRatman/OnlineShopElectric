<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

/**
 * В демо-режиме форма входа заполнена демо-доступом, чтобы заказчик сразу попал в админку.
 */
class Login extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();

        if (config('shop.demo.enabled')) {
            $this->form->fill([
                'email' => config('shop.demo.email'),
                'password' => config('shop.demo.password'),
                'remember' => true,
            ]);
        }
    }
}
