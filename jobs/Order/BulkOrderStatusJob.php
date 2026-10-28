<?php
// Tukar status beberapa pesanan sekali gus (PELAN 18.9 vendor/orders/bulk-status) — tiada batal pukal.
// Setiap pesanan dalam transaksi sendiri; yang tidak dijumpai / peralihan tidak sah dikira `skipped`.
class BulkOrderStatusJob
{
    const MAX_IDS = 200;

    private $vendorId;
    private $cooperative;
    private $userId;
    private $ids;
    private $status;

    public function __construct($vendorId, array $cooperative, $userId, $ids, $status)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->userId = (int) $userId;
        $this->ids = is_array($ids) ? array_values(array_unique(array_map('intval', $ids))) : [];
        $this->status = (string) $status;
    }

    public function run()
    {
        if (!$this->ids || count($this->ids) > self::MAX_IDS || $this->status === 'cancelled') {
            throw new ValidationException(t('order.status_not_allowed'));
        }
        $changed = 0;
        $skipped = 0;
        foreach ($this->ids as $id) {
            try {
                (new UpdateOrderStatusJob($this->vendorId, $this->cooperative, $this->userId, $id, $this->status, '', false))->run();
                $changed++;
            } catch (ValidationException $error) {
                $skipped++;
            }
        }
        return ['changed' => $changed, 'skipped' => $skipped];
    }
}
