<?php
// Pembantu pesanan belian (PELAN 18.8): cari PO dalam koperasi vendor sesi, ringkasan senarai & butiran dengan item.
class PurchaseOrders
{
    public static function find($vendorId, $cooperativeId, $id, $forUpdate = false)
    {
        return Database::fetchOne(
            "SELECT * FROM purchase_orders WHERE vendor_id = ? AND cooperative_id = ? AND id = ?" . ($forUpdate ? ' FOR UPDATE' : ''),
            [(int) $vendorId, (int) $cooperativeId, (int) $id]
        );
    }

    // Senarai tanpa item (+ item_count). $where/$args atas alias po / s
    public static function summaries($vendorId, $cooperativeId, $where = '', array $args = [], $tail = '')
    {
        $rows = Database::fetchAll(
            "SELECT po.*, s.name AS supplier_name,
                    (SELECT COUNT(*) FROM purchase_order_items i WHERE i.vendor_id = po.vendor_id AND i.purchase_order_id = po.id) AS item_count
               FROM purchase_orders po
               JOIN suppliers s ON s.vendor_id = po.vendor_id AND s.id = po.supplier_id
              WHERE po.vendor_id = ? AND po.cooperative_id = ? $where
              ORDER BY po.id DESC $tail",
            array_merge([(int) $vendorId, (int) $cooperativeId], $args)
        );
        return array_map(function ($row) {
            $item = self::shape($row);
            $item['item_count'] = (int) $row['item_count'];
            return $item;
        }, $rows);
    }

    public static function detail($vendorId, $cooperativeId, $id)
    {
        $row = Database::fetchOne(
            "SELECT po.*, s.name AS supplier_name FROM purchase_orders po
               JOIN suppliers s ON s.vendor_id = po.vendor_id AND s.id = po.supplier_id
              WHERE po.vendor_id = ? AND po.cooperative_id = ? AND po.id = ?",
            [(int) $vendorId, (int) $cooperativeId, (int) $id]
        );
        if ($row === null) {
            return null;
        }
        $data = self::shape($row);
        $data['items'] = self::items($vendorId, $id);
        return $data;
    }

    public static function items($vendorId, $purchaseOrderId)
    {
        $rows = Database::fetchAll(
            "SELECT i.id, i.product_id, i.variant_id, p.name AS product_name, v.label AS variant_label, COALESCE(v.sku, p.sku) AS sku,
                    i.qty_ordered, i.qty_received, i.unit_cost
               FROM purchase_order_items i
               JOIN products p ON p.vendor_id = i.vendor_id AND p.id = i.product_id
               LEFT JOIN product_variants v ON v.vendor_id = i.vendor_id AND v.product_id = i.product_id AND v.id = i.variant_id
              WHERE i.vendor_id = ? AND i.purchase_order_id = ?
              ORDER BY i.id",
            [(int) $vendorId, (int) $purchaseOrderId]
        );
        return array_map(function ($row) {
            return [
                'id'            => (int) $row['id'],
                'product_id'    => (int) $row['product_id'],
                'variant_id'    => (int) $row['variant_id'],
                'product_name'  => $row['product_name'],
                'variant_label' => $row['variant_label'],
                'sku'           => (string) $row['sku'],
                'qty_ordered'   => (int) $row['qty_ordered'],
                'qty_received'  => (int) $row['qty_received'],
                'unit_cost'     => (int) $row['unit_cost'],
                'amount'        => (int) $row['qty_ordered'] * (int) $row['unit_cost'],
            ];
        }, $rows);
    }

    private static function shape($row)
    {
        return [
            'id'             => (int) $row['id'],
            'cooperative_id' => (int) $row['cooperative_id'],
            'supplier_id'    => (int) $row['supplier_id'],
            'supplier_name'  => $row['supplier_name'],
            'po_no'          => $row['po_no'],
            'status'         => $row['status'],
            'ordered_at'     => IsoDate::format($row['ordered_at']),
            'received_at'    => IsoDate::format($row['received_at']),
            'note'           => (string) $row['note'],
            'total'          => (int) $row['total'],
        ];
    }
}
