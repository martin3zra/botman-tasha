<?php

namespace App\Services;

class MoneyFormat {

    public static function format($amount = 0) {
        return number_format($amount, 2);
    }
}
