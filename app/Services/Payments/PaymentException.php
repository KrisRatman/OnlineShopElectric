<?php

namespace App\Services\Payments;

use RuntimeException;

/** Ошибка оплаты, текст которой можно показать покупателю. */
class PaymentException extends RuntimeException {}
