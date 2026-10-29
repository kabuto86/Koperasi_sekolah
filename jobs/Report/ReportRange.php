<?php
// Pembantu laporan vendor (vendor/reports/run — PELAN 18.10 "Laporan", 10.2 "Konvensyen B11").
// Jualan = pesanan dibayar (orders.paid_at, store + POS) ikut tarikh bayar dalam julat; refund = refunds selesai ikut tarikh refund
// (pesanan yang pernah dibayar sahaja). Unit bersih = qty − cancelled − returned. Setiap pertanyaan menapis vendor_id + cooperative_id.
class ReportRange
{
    const MAX_DAYS = 366;

    // Unit bersih item (alias oi)
    const NET_QTY = '(oi.qty - oi.cancelled_qty - oi.returned_qty)';

    // [date_from, date_to] 'YYYY-MM-DD' yang disahkan (wajib, tamat ≥ mula, maks 1 tahun)
    public static function dates(array $params)
    {
        $v = new Validator($params);
        $v->required('date_from')->date('date_from')->required('date_to')->date('date_to');
        if (!$v->fails() && $params['date_to'] < $params['date_from']) {
            $v->addError('date_to', t('report.range_invalid'));
        }
        if (!$v->fails() && (new DateTime($params['date_from']))->diff(new DateTime($params['date_to']))->days >= self::MAX_DAYS) {
            $v->addError('date_to', t('report.range_too_long'));
        }
        if ($v->fails()) {
            throw new ValidationException(current($v->errors()), $v->errors());
        }
        return [$params['date_from'], $params['date_to']];
    }

    // " AND {kolum} >= ? AND {kolum} < ?" + [mula 00:00, hari selepas tamat 00:00]
    public static function between($column, $from, $to)
    {
        return [" AND $column >= ? AND $column < ?", [$from . ' 00:00:00', date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00']];
    }

    // [[key, format], …] → lajur API { key, label, format }
    public static function columns(array $spec)
    {
        return array_map(function ($column) {
            return ['key' => $column[0], 'label' => 'report.col.' . $column[0], 'format' => $column[1]];
        }, $spec);
    }

    public static function sum(array $rows, array $keys)
    {
        $totals = [];
        foreach ($keys as $key) {
            $totals[$key] = array_sum(array_column($rows, $key));
        }
        return $totals;
    }

    // Peratus 1 titik perpuluhan (margin, bahagian)
    public static function percent($part, $whole)
    {
        return $whole ? round($part / $whole * 100, 1) : 0;
    }
}
