<?php
// Ringkasan checkout (PELAN 18.12 store/checkout/quote): CartQuote + promosi/kupon, cukai selepas diskaun, kaedah bayaran,
// kod 4 digit, slot ambil & tutup tempahan. customer_id dari sesi.
class GetCheckoutQuoteJob
{
    private $customerId;
    private $cooperative;
    private $couponCode;

    public function __construct($customerId, array $cooperative, $couponCode)
    {
        $this->customerId = (int) $customerId;
        $this->cooperative = $cooperative;
        $this->couponCode = mb_substr((string) $couponCode, 0, 20, 'UTF-8');
    }

    public function run()
    {
        $customer = (new LoadCustomerSessionJob($this->customerId))->run();
        if ($customer === null) {
            throw new ValidationException(t('store.login_required'), [], 401, ['auth_required' => true]);
        }
        return CheckoutQuote::build($customer, $this->cooperative, $this->couponCode)['data'];
    }
}
