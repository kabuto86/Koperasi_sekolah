<?php
// Seorang ahli + baki syer + sejarah transaksi syer (terbaru dahulu). Ahli koperasi / vendor lain → 404.
class GetMemberJob
{
    private $vendorId;
    private $cooperativeId;
    private $memberId;

    public function __construct($vendorId, $cooperativeId, $memberId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->memberId = (int) $memberId;
    }

    public function run()
    {
        $member = Membership::find($this->vendorId, $this->cooperativeId, $this->memberId);
        if ($member === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $transactions = Database::fetchAll(
            "SELECT t.*, u.full_name AS staff_name FROM membership_share_transactions t
               LEFT JOIN vendor_users u ON u.id = t.vendor_user_id AND u.vendor_id = t.vendor_id
              WHERE t.vendor_id = ? AND t.cooperative_id = ? AND t.member_id = ?
              ORDER BY t.transaction_date DESC, t.id DESC LIMIT 200",
            [$this->vendorId, $this->cooperativeId, $this->memberId]
        );
        $result = Membership::shape($member, Membership::balance($this->vendorId, $this->memberId));
        $result['transactions'] = array_map([Membership::class, 'shapeTransaction'], $transactions);
        return $result;
    }
}
