<?php
// Tambah / sunting slot ambil (vendor/school-opening/slot-save — PELAN 18.13).
// Tambah: satu slot untuk setiap darjah dipilih (tiada = satu slot semua darjah). Sunting: satu slot, darjah pertama / semua.
// Tarikh ≥ pickup_starts_on, masa tamat > mula, kapasiti 1–1000. Sunting dalam transaksi: slot dikunci FOR UPDATE dan
// kapasiti mesti ≥ booked yang DIKUNCI (checkout serentak menunggu kunci yang sama).
class SavePickupSlotJob
{
    const MAX_CAPACITY = 1000;

    private $vendorId;
    private $cooperative;
    private $userId;
    private $input;

    // $cooperative = baris VendorAuth::requireCooperative (milik vendor sesi)
    public function __construct($vendorId, array $cooperative, $userId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->userId = (int) $userId;
        $this->input = $input;
    }

    public function run()
    {
        $cooperativeId = (int) $this->cooperative['id'];
        $season = PreorderSeasons::find($this->vendorId, $cooperativeId, (int) ($this->input['season_id'] ?? 0));
        if ($season === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $data = $this->validate($season);
        $id = (int) ($this->input['id'] ?? 0);

        Database::beginTransaction();
        try {
            $ids = $id > 0 ? [$this->update($season, $id, $data)] : $this->insert($season, $data);
            (new WriteActivityLogJob($this->vendorId, $cooperativeId, $this->userId, 'season_save', 'preorder_season', (int) $season['id'],
                (string) $season['school_year']))->run();
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }
        $items = array_values(array_filter(PreorderSeasons::slots($this->vendorId, $season['id']), function ($row) use ($ids) {
            return in_array((int) $row['id'], $ids, true);
        }));
        return ['created' => $id > 0 ? 0 : count($ids), 'items' => array_map([CheckoutQuote::class, 'slotShape'], $items)];
    }

    private function update(array $season, $id, array $data)
    {
        $slot = Database::fetchOne(
            "SELECT id, booked FROM pickup_slots WHERE id = ? AND vendor_id = ? AND preorder_season_id = ? FOR UPDATE",
            [$id, $this->vendorId, (int) $season['id']]
        );
        if ($slot === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        if ($data['capacity'] < (int) $slot['booked']) {
            throw new ValidationException(t('error.check_form'), ['capacity' => t('opening.capacity_below_booked', ['n' => (int) $slot['booked']])]);
        }
        Database::execute(
            "UPDATE pickup_slots SET slot_date = ?, time_start = ?, time_end = ?, capacity = ?, grade_level_code = ? WHERE id = ? AND vendor_id = ?",
            [$data['slot_date'], $data['time_start'], $data['time_end'], $data['capacity'], $data['grades'][0] ?? null, $id, $this->vendorId]
        );
        return $id;
    }

    private function insert(array $season, array $data)
    {
        $ids = [];
        foreach ($data['grades'] ?: [null] as $grade) {
            $ids[] = Database::insert(
                "INSERT INTO pickup_slots (vendor_id, cooperative_id, preorder_season_id, slot_date, time_start, time_end, grade_level_code, capacity)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$this->vendorId, (int) $this->cooperative['id'], (int) $season['id'], $data['slot_date'], $data['time_start'], $data['time_end'],
                    $grade, $data['capacity']]
            );
        }
        return $ids;
    }

    private function validate(array $season)
    {
        $time = '/^([01]\d|2[0-3]):[0-5]\d$/';
        $v = new Validator($this->input);
        $v->required('slot_date')->date('slot_date')
          ->required('time_start')->pattern('time_start', $time)
          ->required('time_end')->pattern('time_end', $time)
          ->required('capacity')->integer('capacity', 1, self::MAX_CAPACITY);
        $errors = $v->errors();
        $date = trim((string) ($this->input['slot_date'] ?? ''));
        $start = trim((string) ($this->input['time_start'] ?? ''));
        $end = trim((string) ($this->input['time_end'] ?? ''));
        if (!isset($errors['slot_date']) && $date < $season['pickup_starts_on']) {
            $v->addError('slot_date', t('opening.slot_before_pickup', ['date' => date('d/m/Y', strtotime($season['pickup_starts_on']))]));
        }
        if (!isset($errors['time_start']) && !isset($errors['time_end']) && $end <= $start) {
            $v->addError('time_end', t('opening.time_order'));
        }
        ValidationException::throwIfFails($v);
        $allowed = (new ListCooperativeGradesJob($this->cooperative['school_type']))->run();
        $wanted = is_array($this->input['grades'] ?? null) ? $this->input['grades'] : [];
        return [
            'slot_date'  => $date,
            'time_start' => $start,
            'time_end'   => $end,
            'capacity'   => (int) $this->input['capacity'],
            'grades'     => array_values(array_intersect($allowed, array_map('strval', $wanted))),
        ];
    }
}
