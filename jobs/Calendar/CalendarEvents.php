<?php
// Pembantu modul kalendar cuti (calendar — PELAN 18.13). blocks_orders = tempahan online ditutup pada hari itu
// (disemak oleh CheckoutQuote::ordersBlocked di store — bukan di sini).
class CalendarEvents
{
    const TYPES = ['school_holiday', 'coop_closed', 'school_opening', 'other'];

    public static function find($vendorId, $cooperativeId, $id)
    {
        return Database::fetchOne(
            "SELECT * FROM calendar_events WHERE id = ? AND vendor_id = ? AND cooperative_id = ?",
            [(int) $id, (int) $vendorId, (int) $cooperativeId]
        );
    }

    public static function shape(array $row)
    {
        return [
            'id'            => (int) $row['id'],
            'title'         => $row['title'],
            'event_type'    => $row['event_type'],
            'starts_on'     => $row['starts_on'],
            'ends_on'       => $row['ends_on'],
            'blocks_orders' => (bool) $row['blocks_orders'],
        ];
    }
}
