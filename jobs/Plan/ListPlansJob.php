<?php
// Senarai pelan (PELAN 18.7 Plan) + bilangan vendor yang melanggan.
class ListPlansJob
{
    const SELECT = "SELECT p.*, (SELECT COUNT(DISTINCT s.vendor_id) FROM subscriptions s JOIN vendors v ON v.id = s.vendor_id
                     WHERE s.plan_id = p.id AND s.status <> 'cancelled' AND v.status <> 'cancelled') AS vendor_count
                    FROM plans p";

    public function run()
    {
        $rows = Database::fetchAll(self::SELECT . " ORDER BY p.sort_order, p.id");
        return array_map([self::class, 'shape'], $rows);
    }

    public static function find($id)
    {
        $row = Database::fetchOne(self::SELECT . " WHERE p.id = ?", [(int) $id]);
        return $row === null ? null : self::shape($row);
    }

    public static function shape($row)
    {
        $features = json_decode((string) $row['features_json'], true);
        return [
            'id'               => (int) $row['id'],
            'code'             => $row['code'],
            'name'             => $row['name'],
            'price_monthly'    => (int) $row['price_monthly'],
            'price_yearly'     => (int) $row['price_yearly'],
            'max_cooperatives' => self::nullableInt($row['max_cooperatives']),
            'max_products'     => self::nullableInt($row['max_products']),
            'max_staff'        => self::nullableInt($row['max_staff']),
            'features'         => is_array($features) ? $features : [],
            'is_active'        => (bool) $row['is_active'],
            'sort_order'       => (int) $row['sort_order'],
            'vendor_count'     => (int) $row['vendor_count'],
        ];
    }

    private static function nullableInt($value)
    {
        return $value === null ? null : (int) $value;
    }
}
