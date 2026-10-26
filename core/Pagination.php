<?php
// Parameter senarai (PELAN 18.2): page (lalai 1), per_page (lalai 20, maks 100).
class Pagination
{
    // [page, per_page, offset]
    public static function from(array $input)
    {
        $page = max(1, (int) ($input['page'] ?? 1));
        $perPage = (int) ($input['per_page'] ?? 20);
        $perPage = $perPage < 1 ? 20 : min($perPage, 100);
        return [$page, $perPage, ($page - 1) * $perPage];
    }

    public static function result(array $items, $total, $page, $perPage)
    {
        return ['items' => $items, 'total' => (int) $total, 'page' => $page, 'per_page' => $perPage];
    }

    // Corak LIKE selamat (escape % dan _)
    public static function like($text)
    {
        return '%' . addcslashes((string) $text, '%_\\') . '%';
    }
}
