<?php

use App\Support\Money;

if (! function_exists('money_rub')) {
    /** Копейки → «1 500 ₽». */
    function money_rub(int $kopecks): string
    {
        return Money::format($kopecks);
    }
}
