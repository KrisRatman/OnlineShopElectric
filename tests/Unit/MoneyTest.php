<?php

use App\Support\Money;

it('переводит копейки в сумму для API ЮKassa без float', function (int $kopecks, string $expected) {
    expect(Money::toDecimal($kopecks))->toBe($expected);
})->with([
    [0, '0.00'],
    [5, '0.05'],
    [149000, '1490.00'],
    [149050, '1490.50'],
    [2599999, '25999.99'],
]);

it('читает сумму из ответа ЮKassa в копейки', function (string $value, int $expected) {
    expect(Money::fromDecimal($value))->toBe($expected);
})->with([
    ['1490.00', 149000],
    ['1490.5', 149050],
    ['1490', 149000],
    ['0.01', 1],
    // Классическая ловушка float: 0.1 + 0.2, 19.99 * 100 = 1998.9999…
    ['19.99', 1999],
    ['1234567.89', 123456789],
]);

it('не принимает мусор вместо суммы', function (string $value) {
    Money::fromDecimal($value);
})->with(['', 'abc', '10.999', '-5.00', '1,50'])->throws(InvalidArgumentException::class);

it('форматирует сумму для покупателя', function () {
    expect(Money::format(149000))->toBe('1 490 ₽')
        ->and(Money::format(149050))->toBe('1 490,50 ₽')
        ->and(Money::format(0))->toBe('0 ₽');
});
