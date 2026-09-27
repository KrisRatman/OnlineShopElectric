<?php

use App\Models\Order;
use App\Models\User;

it('регистрирует покупателя и открывает кабинет', function () {
    $this->post('/register', [
        'name' => 'Анна',
        'email' => 'anna@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertRedirect(route('account'));

    $this->assertAuthenticated();
    expect(User::sole()->is_admin)->toBeFalse();
});

it('не пускает с неверным паролем', function () {
    User::factory()->create(['email' => 'anna@example.com']);

    $this->post('/login', ['email' => 'anna@example.com', 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('в кабинете видны только свои заказы', function () {
    $user = User::factory()->create();
    $mine = Order::factory()->create(['user_id' => $user->id]);
    $foreign = Order::factory()->create();

    $this->actingAs($user)->get('/account')
        ->assertOk()
        ->assertSee("Заказ №{$mine->number}")
        ->assertDontSee("Заказ №{$foreign->number}");
});

it('гостя из кабинета отправляет на вход', function () {
    $this->get('/account')->assertRedirect(route('login'));
});

it('сохраняет телефон в едином формате', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/account/profile', ['name' => 'Анна', 'phone' => '8 (916) 123-45-67'])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->phone)->toBe('+79161234567');
});

it('в админку пускает только администратора', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk();
});
