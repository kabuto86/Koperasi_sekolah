<?php
// Templat e-mel yang boleh disunting owner dan pemegang tempat yang dibenarkan (PELAN 18.7 EmailTemplate).
// Kunci lain (verify_email, reset_password, staff_invite, test_email) guna teks lang/*.php melalui QueueEmailJob.
class EmailTemplateCatalog
{
    const KEYS = [
        'order_new_vendor' => ['{order_no}', '{school_name}', '{customer_name}', '{total}', '{order_url}'],
        'order_receipt'    => ['{order_no}', '{customer_name}', '{school_name}', '{total}', '{items_table}', '{pickup_code}', '{pickup_qr}', '{order_url}'],
        'order_ready'      => ['{order_no}', '{customer_name}', '{child_name}', '{child_class}', '{school_name}', '{opening_hours}'],
        'order_collected'  => ['{order_no}', '{customer_name}', '{child_name}', '{collected_by}', '{collected_at}'],
        'invoice_issued'   => ['{invoice_no}', '{vendor_name}', '{total}', '{due_date}', '{pay_url}'],
        'password_reset'   => ['{name}', '{reset_url}', '{expires_minutes}'],
    ];

    public static function placeholders($key)
    {
        return self::KEYS[$key] ?? null;
    }
}
