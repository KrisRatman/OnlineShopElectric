<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Ссылка на заказ для гостя: /orders/{token}.
            $table->string('token', 64)->unique();
            $table->string('status', 32)->index();

            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 20);
            $table->string('delivery_method', 16);
            $table->string('delivery_address')->nullable();
            $table->text('comment')->nullable();

            // Суммы — в копейках, зафиксированы на момент оформления.
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('delivery_price');
            $table->unsignedInteger('total');

            // До какого момента держим резерв, если заказ не оплачен.
            $table->timestamp('payment_due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'payment_due_at']);
        });

        // Снимок товара: название и цена не меняются, даже если товар потом отредактируют или удалят.
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('product_sku', 64);
            $table->unsignedInteger('price');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('total');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 16)->default('yookassa');
            // id платежа в ЮKassa. Уникальность — основа идемпотентной обработки уведомлений.
            $table->string('provider_payment_id', 64)->nullable()->unique();
            // Повтор запроса с тем же ключом ЮKassa не создаёт второй платёж, а возвращает первый.
            $table->uuid('idempotence_key')->unique();
            $table->string('status', 32)->index();
            $table->unsignedInteger('amount');
            $table->string('confirmation_url', 2048)->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
