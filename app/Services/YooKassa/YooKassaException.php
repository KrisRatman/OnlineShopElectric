<?php

namespace App\Services\YooKassa;

use RuntimeException;

/** ЮKassa недоступна или отклонила запрос. Покупателю — общий текст, подробности — в лог. */
class YooKassaException extends RuntimeException {}
