<?php
// Wang dalam sen (INT) → teks "RM 1,234.50" (sama seperti formatRM() dalam helpers.js). Untuk e-mel & mesej ralat.
class Money
{
    public static function format($sen)
    {
        $amount = (int) $sen;
        return ($amount < 0 ? '-RM ' : 'RM ') . number_format(abs($amount) / 100, 2, '.', ',');
    }
}
