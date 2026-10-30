<?php
// Tambah / sunting musim pre-order (vendor/school-opening/season-save): tahun 2020–2100 unik dalam koperasi,
// tutup ≥ buka, ambil bermula ≥ tarikh tutup.
class SavePreorderSeasonJob
{
    private $vendorId;
    private $cooperativeId;
    private $userId;
    private $input;

    public function __construct($vendorId, $cooperativeId, $userId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->userId = (int) $userId;
        $this->input = $input;
    }

    public function run()
    {
        $id = (int) ($this->input['id'] ?? 0);
        if ($id > 0 && PreorderSeasons::find($this->vendorId, $this->cooperativeId, $id) === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $data = $this->validate($id);
        $values = [$data['school_year'], $data['opens_at'] . ' 00:00:00', $data['closes_at'] . ' 23:59:59', $data['pickup_starts_on'], $data['note']];
        try {
            if ($id > 0) {
                Database::execute(
                    "UPDATE preorder_seasons SET school_year = ?, opens_at = ?, closes_at = ?, pickup_starts_on = ?, note = ?
                      WHERE id = ? AND vendor_id = ? AND cooperative_id = ?",
                    array_merge($values, [$id, $this->vendorId, $this->cooperativeId])
                );
            } else {
                $id = Database::insert(
                    "INSERT INTO preorder_seasons (vendor_id, cooperative_id, school_year, opens_at, closes_at, pickup_starts_on, note)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    array_merge([$this->vendorId, $this->cooperativeId], $values)
                );
            }
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw new ValidationException(t('error.check_form'), ['school_year' => t('opening.duplicate_year')]);
            }
            throw $exception;
        }
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'season_save', 'preorder_season', $id,
            (string) $data['school_year']))->run();
        return PreorderSeasons::shape(PreorderSeasons::find($this->vendorId, $this->cooperativeId, $id));
    }

    private function validate($id)
    {
        $v = new Validator($this->input);
        $v->required('school_year')->integer('school_year', 2020, 2100)
          ->required('opens_at')->date('opens_at')
          ->required('closes_at')->date('closes_at')
          ->required('pickup_starts_on')->date('pickup_starts_on')
          ->maxLength('note', 255);
        $errors = $v->errors();
        $opens = trim((string) ($this->input['opens_at'] ?? ''));
        $closes = trim((string) ($this->input['closes_at'] ?? ''));
        $pickup = trim((string) ($this->input['pickup_starts_on'] ?? ''));
        if (!isset($errors['opens_at']) && !isset($errors['closes_at']) && $closes < $opens) {
            $v->addError('closes_at', t('addon.end_before_start'));
        }
        if (!isset($errors['closes_at']) && !isset($errors['pickup_starts_on']) && $pickup < $closes) {
            $v->addError('pickup_starts_on', t('opening.pickup_after_close'));
        }
        $year = (int) ($this->input['school_year'] ?? 0);
        if (!isset($errors['school_year']) && Database::fetchValue(
            "SELECT 1 FROM preorder_seasons WHERE vendor_id = ? AND cooperative_id = ? AND school_year = ? AND id <> ?",
            [$this->vendorId, $this->cooperativeId, $year, $id]
        )) {
            $v->addError('school_year', t('opening.duplicate_year'));
        }
        ValidationException::throwIfFails($v);
        return [
            'school_year'      => $year,
            'opens_at'         => $opens,
            'closes_at'        => $closes,
            'pickup_starts_on' => $pickup,
            'note'             => trim((string) ($this->input['note'] ?? '')),
        ];
    }
}
