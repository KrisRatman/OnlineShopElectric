<?php

namespace Database\Seeders\Support;

/**
 * Демо-каталог «Вольта»: компьютеры, ноутбуки, смартфоны. Цены — в рублях, в сидере
 * переводятся в копейки.
 */
final class DemoCatalog
{
    /** @return array<string, array{name: string, children: array<string, string>}> */
    public static function categories(): array
    {
        return [
            'kompyutery' => ['name' => 'Компьютеры', 'children' => [
                'igrovye-pk' => 'Игровые ПК',
                'ofisnye-pk' => 'Офисные ПК',
                'monobloki' => 'Моноблоки',
            ]],
            'noutbuki' => ['name' => 'Ноутбуки', 'children' => [
                'igrovye-noutbuki' => 'Игровые ноутбуки',
                'ultrabuki' => 'Ультрабуки',
                'noutbuki-dlya-raboty' => 'Для работы и учёбы',
            ]],
            'smartfony' => ['name' => 'Смартфоны', 'children' => [
                'android' => 'Android-смартфоны',
                'iphone' => 'iPhone',
            ]],
        ];
    }

    /** @return array<string, array{0: string, 1: bool}> slug => [название, в фильтрах] */
    public static function attributes(): array
    {
        return [
            'cpu' => ['Процессор', true],
            'ram' => ['Оперативная память', true],
            'storage' => ['Накопитель', true],
            'gpu' => ['Видеокарта', true],
            'screen' => ['Диагональ экрана', true],
            'refresh' => ['Частота экрана', false],
            'os' => ['Операционная система', true],
            'camera' => ['Основная камера', false],
            'battery' => ['Аккумулятор', false],
            'color' => ['Цвет', true],
            'weight' => ['Вес', false],
            'warranty' => ['Гарантия', false],
        ];
    }

    /**
     * @return list<array{
     *     category: string, brand: string, name: string, sku: string, price: int, old: ?int,
     *     stock: int, featured: bool, image: string, accent: string, body: string, specs: array<string, string>
     * }>
     */
    public static function products(): array
    {
        $p = fn (string $category, string $brand, string $name, string $sku, int $price, ?int $old, int $stock, bool $featured, string $image, string $accent, string $body, array $specs) => compact(
            'category', 'brand', 'name', 'sku', 'price', 'old', 'stock', 'featured', 'image', 'accent', 'body', 'specs',
        );

        return [
            // Игровые ПК
            $p('igrovye-pk', 'Вольт', 'Игровой ПК Вольт Start Ryzen 5 7500F / RTX 4060', 'VLT-PC-001', 89990, 99990, 7, true, 'tower', '#22d3ee', '#111827',
                ['cpu' => 'AMD Ryzen 5 7500F', 'ram' => '16 ГБ', 'storage' => '1 ТБ SSD', 'gpu' => 'GeForce RTX 4060', 'os' => 'Без ОС', 'color' => 'Чёрный', 'warranty' => '2 года']),
            $p('igrovye-pk', 'Вольт', 'Игровой ПК Вольт Pro Core i5-14400F / RTX 4070 Super', 'VLT-PC-002', 149990, null, 4, true, 'tower', '#a855f7', '#111827',
                ['cpu' => 'Intel Core i5-14400F', 'ram' => '32 ГБ', 'storage' => '1 ТБ SSD', 'gpu' => 'GeForce RTX 4070 Super', 'os' => 'Windows 11', 'color' => 'Чёрный', 'warranty' => '2 года']),
            $p('igrovye-pk', 'Вольт', 'Игровой ПК Вольт Ultra Ryzen 7 7800X3D / RTX 4080 Super', 'VLT-PC-003', 259990, null, 2, false, 'tower', '#f43f5e', '#f1f5f9',
                ['cpu' => 'AMD Ryzen 7 7800X3D', 'ram' => '32 ГБ', 'storage' => '2 ТБ SSD', 'gpu' => 'GeForce RTX 4080 Super', 'os' => 'Windows 11', 'color' => 'Белый', 'warranty' => '3 года']),
            $p('igrovye-pk', 'MSI', 'MSI MAG Infinite S3 Core i7-14700F / RTX 4060 Ti', 'MSI-INF-S3', 139990, 154990, 3, false, 'tower', '#ef4444', '#18181b',
                ['cpu' => 'Intel Core i7-14700F', 'ram' => '16 ГБ', 'storage' => '1 ТБ SSD', 'gpu' => 'GeForce RTX 4060 Ti', 'os' => 'Windows 11', 'color' => 'Чёрный', 'warranty' => '1 год']),
            $p('igrovye-pk', 'ASUS', 'ASUS ROG Strix G16CH Core i7-13700F / RTX 4070', 'ASUS-G16CH', 179990, null, 0, false, 'tower', '#ec4899', '#18181b',
                ['cpu' => 'Intel Core i7-13700F', 'ram' => '32 ГБ', 'storage' => '1 ТБ SSD', 'gpu' => 'GeForce RTX 4070', 'os' => 'Windows 11', 'color' => 'Чёрный', 'warranty' => '1 год']),
            $p('igrovye-pk', 'Lenovo', 'Lenovo Legion Tower 5 Ryzen 7 7700 / RTX 4060 Ti', 'LEN-LT5', 144990, null, 5, false, 'tower', '#38bdf8', '#27272a',
                ['cpu' => 'AMD Ryzen 7 7700', 'ram' => '16 ГБ', 'storage' => '1 ТБ SSD', 'gpu' => 'GeForce RTX 4060 Ti', 'os' => 'Windows 11', 'color' => 'Серый', 'warranty' => '1 год']),

            // Офисные ПК
            $p('ofisnye-pk', 'Вольт', 'Компьютер Вольт Office Mini Core i3-12100', 'VLT-OF-001', 34990, null, 15, false, 'office', '#3366ff', '#e2e8f0',
                ['cpu' => 'Intel Core i3-12100', 'ram' => '8 ГБ', 'storage' => '256 ГБ SSD', 'gpu' => 'Встроенная', 'os' => 'Без ОС', 'color' => 'Серый', 'warranty' => '2 года']),
            $p('ofisnye-pk', 'HP', 'HP Pro Tower 400 G9 Core i5-12500', 'HP-PT400', 62990, null, 8, false, 'office', '#0ea5e9', '#cbd5e1',
                ['cpu' => 'Intel Core i5-12500', 'ram' => '16 ГБ', 'storage' => '512 ГБ SSD', 'gpu' => 'Встроенная', 'os' => 'Windows 11', 'color' => 'Серый', 'warranty' => '1 год']),
            $p('ofisnye-pk', 'Lenovo', 'Lenovo ThinkCentre neo 50s Core i5-13400', 'LEN-TC50S', 67990, 72990, 6, false, 'office', '#ef4444', '#1f2937',
                ['cpu' => 'Intel Core i5-13400', 'ram' => '16 ГБ', 'storage' => '512 ГБ SSD', 'gpu' => 'Встроенная', 'os' => 'Windows 11', 'color' => 'Чёрный', 'warranty' => '1 год']),
            $p('ofisnye-pk', 'Acer', 'Acer Veriton X Ryzen 5 5600G', 'ACR-VX', 49990, null, 2, false, 'office', '#22c55e', '#334155',
                ['cpu' => 'AMD Ryzen 5 5600G', 'ram' => '16 ГБ', 'storage' => '512 ГБ SSD', 'gpu' => 'Встроенная', 'os' => 'Без ОС', 'color' => 'Чёрный', 'warranty' => '1 год']),

            // Моноблоки
            $p('monobloki', 'Apple', 'Apple iMac 24" M3 8/256 ГБ', 'APL-IMAC-M3', 159990, null, 4, true, 'aio', '#3b82f6', '#93c5fd',
                ['cpu' => 'Apple M3', 'ram' => '8 ГБ', 'storage' => '256 ГБ SSD', 'gpu' => 'Встроенная', 'screen' => '24"', 'os' => 'macOS', 'color' => 'Синий', 'weight' => '4,5 кг', 'warranty' => '1 год']),
            $p('monobloki', 'HP', 'HP All-in-One 24 Core i5-1335U 16/512 ГБ', 'HP-AIO24', 74990, null, 6, false, 'aio', '#6366f1', '#e2e8f0',
                ['cpu' => 'Intel Core i5-1335U', 'ram' => '16 ГБ', 'storage' => '512 ГБ SSD', 'gpu' => 'Встроенная', 'screen' => '23,8"', 'os' => 'Windows 11', 'color' => 'Белый', 'warranty' => '1 год']),
            $p('monobloki', 'Lenovo', 'Lenovo IdeaCentre AIO 3 27" Ryzen 5 7530U', 'LEN-AIO3', 69990, 79990, 3, false, 'aio', '#14b8a6', '#1f2937',
                ['cpu' => 'AMD Ryzen 5 7530U', 'ram' => '16 ГБ', 'storage' => '512 ГБ SSD', 'gpu' => 'Встроенная', 'screen' => '27"', 'os' => 'Windows 11', 'color' => 'Чёрный', 'warranty' => '1 год']),

            // Игровые ноутбуки
            $p('igrovye-noutbuki', 'ASUS', 'ASUS TUF Gaming A15 Ryzen 7 7735HS / RTX 4060', 'ASUS-TUF-A15', 104990, 119990, 9, true, 'laptop', '#f59e0b', '#27272a',
                ['cpu' => 'AMD Ryzen 7 7735HS', 'ram' => '16 ГБ', 'storage' => '512 ГБ SSD', 'gpu' => 'GeForce RTX 4060', 'screen' => '15,6"', 'refresh' => '144 Гц', 'os' => 'Без ОС', 'color' => 'Серый', 'weight' => '2,2 кг', 'warranty' => '1 год']),
            $p('igrovye-noutbuki', 'MSI', 'MSI Katana 15 Core i7-13620H / RTX 4070', 'MSI-KAT15', 129990, null, 5, false, 'laptop', '#ef4444', '#18181b',
                ['cpu' => 'Intel Core i7-13620H', 'ram' => '16 ГБ', 'storage' => '1 ТБ SSD', 'gpu' => 'GeForce RTX 4070', 'screen' => '15,6"', 'refresh' => '144 Гц', 'os' => 'Windows 11', 'color' => 'Чёрный', 'weight' => '2,3 кг', 'warranty' => '1 год']),
            $p('igrovye-noutbuki', 'Lenovo', 'Lenovo Legion 5 16IRX9 Core i7-14650HX / RTX 4060', 'LEN-LEG5', 164990, null, 3, true, 'laptop', '#8b5cf6', '#334155',
                ['cpu' => 'Intel Core i7-14650HX', 'ram' => '32 ГБ', 'storage' => '1 ТБ SSD', 'gpu' => 'GeForce RTX 4060', 'screen' => '16"', 'refresh' => '165 Гц', 'os' => 'Windows 11', 'color' => 'Серый', 'weight' => '2,4 кг', 'warranty' => '2 года']),
            $p('igrovye-noutbuki', 'Acer', 'Acer Nitro V 15 Core i5-13420H / RTX 3050', 'ACR-NITROV', 74990, null, 11, false, 'laptop', '#84cc16', '#18181b',
                ['cpu' => 'Intel Core i5-13420H', 'ram' => '16 ГБ', 'storage' => '512 ГБ SSD', 'gpu' => 'GeForce RTX 3050', 'screen' => '15,6"', 'refresh' => '144 Гц', 'os' => 'Без ОС', 'color' => 'Чёрный', 'weight' => '2,1 кг', 'warranty' => '1 год']),
            $p('igrovye-noutbuki', 'ASUS', 'ASUS ROG Zephyrus G14 Ryzen 9 8945HS / RTX 4070', 'ASUS-G14', 219990, null, 1, false, 'laptop', '#e11d48', '#e2e8f0',
                ['cpu' => 'AMD Ryzen 9 8945HS', 'ram' => '32 ГБ', 'storage' => '1 ТБ SSD', 'gpu' => 'GeForce RTX 4070', 'screen' => '14"', 'refresh' => '120 Гц', 'os' => 'Windows 11', 'color' => 'Белый', 'weight' => '1,5 кг', 'warranty' => '2 года']),

            // Ультрабуки
            $p('ultrabuki', 'Apple', 'Apple MacBook Air 13 M3 8/256 ГБ', 'APL-MBA13-M3', 119990, null, 10, true, 'laptop', '#60a5fa', '#94a3b8',
                ['cpu' => 'Apple M3', 'ram' => '8 ГБ', 'storage' => '256 ГБ SSD', 'gpu' => 'Встроенная', 'screen' => '13,6"', 'refresh' => '60 Гц', 'os' => 'macOS', 'color' => 'Серебристый', 'weight' => '1,24 кг', 'warranty' => '1 год']),
            $p('ultrabuki', 'Apple', 'Apple MacBook Pro 14 M4 16/512 ГБ', 'APL-MBP14-M4', 199990, null, 4, false, 'laptop', '#a855f7', '#334155',
                ['cpu' => 'Apple M4', 'ram' => '16 ГБ', 'storage' => '512 ГБ SSD', 'gpu' => 'Встроенная', 'screen' => '14,2"', 'refresh' => '120 Гц', 'os' => 'macOS', 'color' => 'Серый', 'weight' => '1,55 кг', 'warranty' => '1 год']),
            $p('ultrabuki', 'ASUS', 'ASUS Zenbook 14 OLED Core Ultra 7 155H', 'ASUS-ZB14', 109990, 124990, 6, false, 'laptop', '#06b6d4', '#1e3a8a',
                ['cpu' => 'Intel Core Ultra 7 155H', 'ram' => '16 ГБ', 'storage' => '1 ТБ SSD', 'gpu' => 'Встроенная', 'screen' => '14"', 'refresh' => '120 Гц', 'os' => 'Windows 11', 'color' => 'Синий', 'weight' => '1,2 кг', 'warranty' => '1 год']),
            $p('ultrabuki', 'HUAWEI', 'HUAWEI MateBook X Pro Core Ultra 7 155H', 'HW-MBXPRO', 169990, null, 2, false, 'laptop', '#f472b6', '#475569',
                ['cpu' => 'Intel Core Ultra 7 155H', 'ram' => '16 ГБ', 'storage' => '1 ТБ SSD', 'gpu' => 'Встроенная', 'screen' => '14,2"', 'refresh' => '120 Гц', 'os' => 'Windows 11', 'color' => 'Серый', 'weight' => '0,98 кг', 'warranty' => '1 год']),

            // Для работы и учёбы
            $p('noutbuki-dlya-raboty', 'Lenovo', 'Lenovo IdeaPad Slim 3 15 Ryzen 5 7520U 8/512 ГБ', 'LEN-IPS3', 44990, null, 14, true, 'laptop', '#3366ff', '#64748b',
                ['cpu' => 'AMD Ryzen 5 7520U', 'ram' => '8 ГБ', 'storage' => '512 ГБ SSD', 'gpu' => 'Встроенная', 'screen' => '15,6"', 'refresh' => '60 Гц', 'os' => 'Без ОС', 'color' => 'Серый', 'weight' => '1,6 кг', 'warranty' => '1 год']),
            $p('noutbuki-dlya-raboty', 'HP', 'HP 250 G10 Core i5-1335U 8/512 ГБ', 'HP-250G10', 52990, null, 8, false, 'laptop', '#0ea5e9', '#1f2937',
                ['cpu' => 'Intel Core i5-1335U', 'ram' => '8 ГБ', 'storage' => '512 ГБ SSD', 'gpu' => 'Встроенная', 'screen' => '15,6"', 'refresh' => '60 Гц', 'os' => 'Windows 11', 'color' => 'Чёрный', 'weight' => '1,8 кг', 'warranty' => '1 год']),
            $p('noutbuki-dlya-raboty', 'Acer', 'Acer Aspire 3 Core i3-1215U 8/256 ГБ', 'ACR-ASP3', 36990, 41990, 12, false, 'laptop', '#10b981', '#94a3b8',
                ['cpu' => 'Intel Core i3-1215U', 'ram' => '8 ГБ', 'storage' => '256 ГБ SSD', 'gpu' => 'Встроенная', 'screen' => '15,6"', 'refresh' => '60 Гц', 'os' => 'Без ОС', 'color' => 'Серебристый', 'weight' => '1,7 кг', 'warranty' => '1 год']),
            $p('noutbuki-dlya-raboty', 'Xiaomi', 'Xiaomi RedmiBook 15 Core i5-11320H 16/512 ГБ', 'XM-RB15', 49990, null, 0, false, 'laptop', '#f97316', '#475569',
                ['cpu' => 'Intel Core i5-11320H', 'ram' => '16 ГБ', 'storage' => '512 ГБ SSD', 'gpu' => 'Встроенная', 'screen' => '15,6"', 'refresh' => '60 Гц', 'os' => 'Windows 11', 'color' => 'Серый', 'weight' => '1,8 кг', 'warranty' => '1 год']),

            // Android
            $p('android', 'Samsung', 'Samsung Galaxy S24 8/256 ГБ', 'SAM-S24', 79990, 89990, 12, true, 'phone', '#8b5cf6', '#c4b5fd',
                ['cpu' => 'Exynos 2400', 'ram' => '8 ГБ', 'storage' => '256 ГБ', 'screen' => '6,2"', 'refresh' => '120 Гц', 'os' => 'Android', 'camera' => '50 Мп', 'battery' => '4000 мА·ч', 'color' => 'Фиолетовый', 'weight' => '167 г', 'warranty' => '1 год']),
            $p('android', 'Samsung', 'Samsung Galaxy A55 8/256 ГБ', 'SAM-A55', 39990, null, 20, false, 'phone', '#38bdf8', '#bae6fd',
                ['cpu' => 'Exynos 1480', 'ram' => '8 ГБ', 'storage' => '256 ГБ', 'screen' => '6,6"', 'refresh' => '120 Гц', 'os' => 'Android', 'camera' => '50 Мп', 'battery' => '5000 мА·ч', 'color' => 'Голубой', 'weight' => '213 г', 'warranty' => '1 год']),
            $p('android', 'Xiaomi', 'Xiaomi 14T 12/256 ГБ', 'XM-14T', 54990, null, 9, false, 'phone', '#64748b', '#334155',
                ['cpu' => 'MediaTek Dimensity 8300-Ultra', 'ram' => '12 ГБ', 'storage' => '256 ГБ', 'screen' => '6,67"', 'refresh' => '144 Гц', 'os' => 'Android', 'camera' => '50 Мп', 'battery' => '5000 мА·ч', 'color' => 'Серый', 'weight' => '195 г', 'warranty' => '1 год']),
            $p('android', 'Xiaomi', 'Xiaomi Redmi Note 13 Pro 8/256 ГБ', 'XM-RN13P', 27990, 31990, 25, true, 'phone', '#a78bfa', '#1e1b4b',
                ['cpu' => 'Snapdragon 7s Gen 2', 'ram' => '8 ГБ', 'storage' => '256 ГБ', 'screen' => '6,67"', 'refresh' => '120 Гц', 'os' => 'Android', 'camera' => '200 Мп', 'battery' => '5100 мА·ч', 'color' => 'Фиолетовый', 'weight' => '187 г', 'warranty' => '1 год']),
            $p('android', 'Google', 'Google Pixel 8a 8/128 ГБ', 'GGL-PX8A', 44990, null, 3, false, 'phone', '#34d399', '#d1fae5',
                ['cpu' => 'Google Tensor G3', 'ram' => '8 ГБ', 'storage' => '128 ГБ', 'screen' => '6,1"', 'refresh' => '120 Гц', 'os' => 'Android', 'camera' => '64 Мп', 'battery' => '4492 мА·ч', 'color' => 'Зелёный', 'weight' => '188 г', 'warranty' => '1 год']),
            $p('android', 'realme', 'realme 12 Pro+ 12/512 ГБ', 'RLM-12PP', 36990, null, 7, false, 'phone', '#f59e0b', '#1e3a8a',
                ['cpu' => 'Snapdragon 7s Gen 2', 'ram' => '12 ГБ', 'storage' => '512 ГБ', 'screen' => '6,7"', 'refresh' => '120 Гц', 'os' => 'Android', 'camera' => '50 Мп', 'battery' => '5000 мА·ч', 'color' => 'Синий', 'weight' => '196 г', 'warranty' => '1 год']),

            // iPhone
            $p('iphone', 'Apple', 'Apple iPhone 15 128 ГБ', 'APL-IP15-128', 79990, null, 10, true, 'phone', '#f472b6', '#fbcfe8',
                ['cpu' => 'Apple A16 Bionic', 'ram' => '6 ГБ', 'storage' => '128 ГБ', 'screen' => '6,1"', 'refresh' => '60 Гц', 'os' => 'iOS', 'camera' => '48 Мп', 'battery' => '3349 мА·ч', 'color' => 'Розовый', 'weight' => '171 г', 'warranty' => '1 год']),
            $p('iphone', 'Apple', 'Apple iPhone 15 Pro 256 ГБ', 'APL-IP15P-256', 119990, null, 5, false, 'phone', '#a8a29e', '#57534e',
                ['cpu' => 'Apple A17 Pro', 'ram' => '8 ГБ', 'storage' => '256 ГБ', 'screen' => '6,1"', 'refresh' => '120 Гц', 'os' => 'iOS', 'camera' => '48 Мп', 'battery' => '3274 мА·ч', 'color' => 'Титановый', 'weight' => '187 г', 'warranty' => '1 год']),
            $p('iphone', 'Apple', 'Apple iPhone 16 128 ГБ', 'APL-IP16-128', 94990, null, 8, false, 'phone', '#2dd4bf', '#99f6e4',
                ['cpu' => 'Apple A18', 'ram' => '8 ГБ', 'storage' => '128 ГБ', 'screen' => '6,1"', 'refresh' => '60 Гц', 'os' => 'iOS', 'camera' => '48 Мп', 'battery' => '3561 мА·ч', 'color' => 'Бирюзовый', 'weight' => '170 г', 'warranty' => '1 год']),
            $p('iphone', 'Apple', 'Apple iPhone 13 128 ГБ', 'APL-IP13-128', 54990, 59990, 2, false, 'phone', '#3b82f6', '#1e293b',
                ['cpu' => 'Apple A15 Bionic', 'ram' => '4 ГБ', 'storage' => '128 ГБ', 'screen' => '6,1"', 'refresh' => '60 Гц', 'os' => 'iOS', 'camera' => '12 Мп', 'battery' => '3240 мА·ч', 'color' => 'Тёмная ночь', 'weight' => '173 г', 'warranty' => '1 год']),
        ];
    }

    /** Описание по типу товара: два абзаца, без выдуманных обещаний. */
    public static function description(array $product): string
    {
        $s = $product['specs'];

        return match ($product['image']) {
            'tower' => "Готовый игровой компьютер на {$s['cpu']} с видеокартой {$s['gpu']}. {$s['ram']} оперативной памяти и {$s['storage']} хватит для современных игр в высоких настройках, стриминга и монтажа.\n\nСистема собрана и протестирована под нагрузкой, кабели уложены, в корпусе продуманный воздушный поток. Гарантия — {$s['warranty']}.",
            'office' => "Надёжный компьютер для офиса и дома на {$s['cpu']}. {$s['ram']} памяти и {$s['storage']} — быстрый старт системы, работа с документами, браузером и видеосвязью без задержек.\n\nКомпактный корпус, тихое охлаждение и удобные порты на передней панели. Гарантия — {$s['warranty']}.",
            'aio' => "Моноблок с экраном {$s['screen']}: компьютер встроен в монитор, на столе — только один кабель питания. Процессор {$s['cpu']}, {$s['ram']} памяти и {$s['storage']}.\n\nПодходит для работы, учёбы и домашних развлечений. Гарантия — {$s['warranty']}.",
            'laptop' => "Ноутбук с экраном {$s['screen']} ({$s['refresh']}) на процессоре {$s['cpu']}. {$s['ram']} оперативной памяти, {$s['storage']}".($s['gpu'] !== 'Встроенная' ? ", видеокарта {$s['gpu']}" : '').". Вес — {$s['weight']}.\n\nОперационная система: {$s['os']}. Гарантия — {$s['warranty']}.",
            'phone' => "Смартфон с экраном {$s['screen']} и частотой {$s['refresh']}, процессор {$s['cpu']}. {$s['ram']} оперативной и {$s['storage']} встроенной памяти, основная камера {$s['camera']}, аккумулятор {$s['battery']}.\n\nЦвет — {$s['color']}, вес {$s['weight']}. Официальная гарантия — {$s['warranty']}.",
        };
    }
}
