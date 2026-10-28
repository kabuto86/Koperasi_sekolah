<?php
// Pilihan slot ambil untuk tapisan pesanan / picking list / label (PELAN 18.9 vendor/orders/slot-options).
// [] jika modul school_opening tidak aktif — tapisan slot disembunyikan di UI.
class ListOrderSlotOptionsJob
{
    private $cooperative;

    public function __construct(array $cooperative)
    {
        $this->cooperative = $cooperative;
    }

    public function run()
    {
        if (!StoreSchool::hasModule($this->cooperative, 'school_opening')) {
            return [];
        }
        $rows = Database::fetchAll(
            "SELECT id, slot_date, time_start, time_end, grade_level_code, capacity, booked FROM pickup_slots
              WHERE vendor_id = ? AND cooperative_id = ? ORDER BY slot_date, time_start, id",
            [(int) $this->cooperative['vendor_id'], (int) $this->cooperative['id']]
        );
        return array_map([CheckoutQuote::class, 'slotShape'], $rows);
    }
}
