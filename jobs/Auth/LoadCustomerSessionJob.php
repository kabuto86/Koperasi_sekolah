<?php
// Ibu bapa untuk sesi store semasa — aktif & belum padam akaun. null = sesi tidak sah lagi.
class LoadCustomerSessionJob
{
    private $customerId;

    public function __construct($customerId)
    {
        $this->customerId = (int) $customerId;
    }

    public function run()
    {
        $row = Database::fetchOne(
            "SELECT id, full_name, phone, email, relationship, language, email_verified_at
               FROM customers WHERE id = ? AND is_active = 1 AND deleted_at IS NULL",
            [$this->customerId]
        );
        if ($row === null) {
            return null;
        }
        $row['id'] = (int) $row['id'];
        return $row;
    }

    // Bentuk awam (PELAN 18.12) — tiada id dalaman, tiada hash
    public static function publicShape($row)
    {
        return [
            'full_name'      => $row['full_name'],
            'phone'          => $row['phone'],
            'email'          => $row['email'],
            'relationship'   => $row['relationship'],
            'language'       => $row['language'],
            'email_verified' => $row['email_verified_at'] !== null,
        ];
    }
}
