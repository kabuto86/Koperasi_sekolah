<?php
// Pembantu modul jualan murah (clearance — PELAN 18.13): jualan & item dicari ikut vendor sesi + koperasi.
// Harga jualan dipakai oleh store melalui StorePricing (bukan di sini). variant_id 0 = semua saiz.
class ClearanceSales
{
    // Alias jadual clearance_sales mesti `s`
    const STATE_SQL = "(CASE WHEN s.is_active = 0 THEN 'inactive' WHEN s.starts_at > NOW() THEN 'scheduled' WHEN s.ends_at < NOW() THEN 'ended' ELSE 'active' END)";

    public static function find($vendorId, $cooperativeId, $id)
    {
        return Database::fetchOne(
            "SELECT s.*, " . self::STATE_SQL . " AS state,
                    (SELECT COUNT(*) FROM clearance_items i WHERE i.vendor_id = s.vendor_id AND i.clearance_sale_id = s.id) AS item_count
               FROM clearance_sales s WHERE s.id = ? AND s.vendor_id = ? AND s.cooperative_id = ?",
            [(int) $id, (int) $vendorId, (int) $cooperativeId]
        );
    }

    public static function shape(array $row)
    {
        return [
            'id'         => (int) $row['id'],
            'name'       => $row['name'],
            'starts_on'  => substr($row['starts_at'], 0, 10),
            'ends_on'    => substr($row['ends_at'], 0, 10),
            'is_active'  => (bool) $row['is_active'],
            'state'      => $row['state'],
            'item_count' => (int) $row['item_count'],
        ];
    }

    public static function findItem($vendorId, $saleId, $itemId)
    {
        return Database::fetchOne(
            "SELECT * FROM clearance_items WHERE id = ? AND vendor_id = ? AND clearance_sale_id = ?",
            [(int) $itemId, (int) $vendorId, (int) $saleId]
        );
    }

    // Item jualan dalam bentuk ClearanceItem (harga biasa & baki stok semasa)
    public static function items($vendorId, $cooperativeId, $saleId, $itemId = null)
    {
        $args = [(int) $cooperativeId, (int) $cooperativeId, (int) $vendorId, (int) $saleId];
        $extra = '';
        if ($itemId !== null) {
            $extra = ' AND i.id = ?';
            $args[] = (int) $itemId;
        }
        $rows = Database::fetchAll(
            "SELECT i.id, i.product_id, i.variant_id, i.sale_price, i.qty_limit,
                    p.name AS product_name, p.sku AS product_sku, p.price, p.has_variants,
                    v.label AS variant_label, v.sku AS variant_sku, v.price_delta,
                    st.qty_on_hand, st.qty_reserved
               FROM clearance_items i
               LEFT JOIN products p ON p.id = i.product_id AND p.vendor_id = i.vendor_id AND p.cooperative_id = ?
               LEFT JOIN product_variants v ON v.id = i.variant_id AND v.vendor_id = i.vendor_id AND v.product_id = i.product_id
               LEFT JOIN stock_levels st ON st.vendor_id = i.vendor_id AND st.cooperative_id = ? AND st.product_id = i.product_id
                                        AND st.variant_id = i.variant_id
              WHERE i.vendor_id = ? AND i.clearance_sale_id = ? $extra
              ORDER BY i.id",
            $args
        );
        return array_map([self::class, 'itemShape'], $rows);
    }

    public static function regularPrice(array $product, $variant)
    {
        return max(0, (int) $product['price'] + ($variant ? (int) $variant['price_delta'] : 0));
    }

    private static function itemShape(array $row)
    {
        $variantId = (int) $row['variant_id'];
        $regular = $row['product_name'] === null ? 0 : max(0, (int) $row['price'] + ($variantId ? (int) $row['price_delta'] : 0));
        $salePrice = (int) $row['sale_price'];
        $allSizes = $variantId === 0 && (int) $row['has_variants'] === 1;
        return [
            'id'               => (int) $row['id'],
            'product_id'       => (int) $row['product_id'],
            'variant_id'       => $variantId ?: null,
            'product_name'     => $row['product_name'] ?? t('store.cart.removed_product'),
            'sku'              => (string) ($variantId ? $row['variant_sku'] : $row['product_sku']),
            'variant_label'    => $variantId ? $row['variant_label'] : null,
            'has_variants'     => (bool) $row['has_variants'],
            'regular_price'    => $regular,
            'sale_price'       => $salePrice,
            'qty_limit'        => $row['qty_limit'] === null ? null : (int) $row['qty_limit'],
            'discount_percent' => $regular > 0 ? (int) round((1 - $salePrice / $regular) * 100) : 0,
            'available'        => $allSizes || $row['qty_on_hand'] === null ? null : max(0, (int) $row['qty_on_hand'] - (int) $row['qty_reserved']),
        ];
    }
}
