<?php
// Musim + slot ambil (vendor/school-opening/slots).
class ListPickupSlotsJob
{
    private $vendorId;
    private $cooperativeId;
    private $seasonId;

    public function __construct($vendorId, $cooperativeId, $seasonId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->seasonId = (int) $seasonId;
    }

    public function run()
    {
        $season = PreorderSeasons::find($this->vendorId, $this->cooperativeId, $this->seasonId);
        if ($season === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        return [
            'season' => PreorderSeasons::shape($season),
            'items'  => array_map([CheckoutQuote::class, 'slotShape'], PreorderSeasons::slots($this->vendorId, $this->seasonId)),
        ];
    }
}
