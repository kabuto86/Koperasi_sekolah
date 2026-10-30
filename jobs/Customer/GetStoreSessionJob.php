<?php
// Maklumat sesi untuk header store (PELAN 18.12 store/session): ibu bapa (atau null), bilangan item troli
// sekolah semasa (slug) dan sekolah anak-anak. customer_id dari sesi sahaja; slug hanya memilih troli.
class GetStoreSessionJob
{
    private $customer;
    private $slug;

    // $customer = baris LoadCustomerSessionJob atau null
    public function __construct($customer, $slug)
    {
        $this->customer = $customer;
        $this->slug = (string) $slug;
    }

    public function run()
    {
        if ($this->customer === null) {
            return ['customer' => null, 'cart_count' => 0, 'schools' => []];
        }
        $customerId = (int) $this->customer['id'];

        $schools = Database::fetchAll(
            "SELECT DISTINCT c.id, c.vendor_id, c.slug, c.school_name, c.school_type, c.state, c.district
               FROM children ch JOIN cooperatives c ON c.id = ch.cooperative_id AND c.vendor_id = ch.vendor_id
              WHERE ch.customer_id = ? AND ch.is_active = 1 AND c.status = 'active'
              ORDER BY c.school_name",
            [$customerId]
        );
        // id dalaman dibuang; app_enabled = modul mobile_app (B15)
        foreach ($schools as $index => $school) {
            $schools[$index] = StoreSchool::publicShape($school) + ['app_enabled' => StoreSchool::appEnabled($school)];
        }
        $cartCount = 0;
        if ($this->slug !== '') {
            $cartCount = (int) Database::fetchValue(
                "SELECT COALESCE(SUM(ci.qty), 0)
                   FROM carts ca JOIN cooperatives c ON c.id = ca.cooperative_id
                   JOIN cart_items ci ON ci.cart_id = ca.id
                  WHERE ca.customer_id = ? AND c.slug = ? AND c.status = 'active'",
                [$customerId, $this->slug]
            );
        }
        return [
            'customer'   => LoadCustomerSessionJob::publicShape($this->customer),
            'cart_count' => $cartCount,
            'schools'    => $schools,
        ];
    }
}
