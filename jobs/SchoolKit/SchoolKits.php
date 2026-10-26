<?php
// Pembantu kit sekolah (PELAN 18.9). Setiap pertanyaan menapis vendor_id + cooperative_id.
// Kit bukan harga pakej (PELAN 16.7): jumlah = harga semasa setiap item aktif × kuantiti, dikira semula setiap kali.
class SchoolKits
{
    const MAX_ITEMS = 100;
    const MAX_QTY = 999;

    // Baris kit dalam koperasi vendor sesi, atau null
    public static function find($vendorId, $cooperativeId, $kitId, $forUpdate = false)
    {
        return Database::fetchOne(
            "SELECT * FROM school_kits WHERE vendor_id = ? AND cooperative_id = ? AND id = ?" . ($forUpdate ? ' FOR UPDATE' : ''),
            [(int) $vendorId, (int) $cooperativeId, (int) $kitId]
        );
    }

    // Kit DITERBITKAN untuk darjah & tahun dalam koperasi (untuk store — B6). Draf tidak pernah dipulangkan.
    // $cooperative = baris koperasi aktif dari slug (vendor_id diambil dari baris itu, bukan input)
    public static function findPublished(array $cooperative, $gradeCode, $schoolYear)
    {
        return Database::fetchOne(
            "SELECT * FROM school_kits
              WHERE vendor_id = ? AND cooperative_id = ? AND grade_level_code = ? AND school_year = ? AND is_published = 1",
            [(int) $cooperative['vendor_id'], (int) $cooperative['id'], (string) $gradeCode, (int) $schoolYear]
        );
    }

    // Tahun persekolahan yang boleh disunting: tahun lepas hingga dua tahun hadapan (musim pre-order bermula Okt–Dis)
    public static function yearAllowed($year)
    {
        $current = (int) date('Y');
        return $year >= $current - 1 && $year <= $current + 2;
    }

    // KitSummary untuk setiap baris kit
    public static function summaries($vendorId, array $kits)
    {
        $items = self::items($vendorId, array_column($kits, 'id'));
        return array_map(function ($kit) use ($items) {
            return self::summary($kit, $items[(int) $kit['id']] ?? []);
        }, $kits);
    }

    // KitDetail = KitSummary + items
    public static function detail($vendorId, $cooperativeId, $kitId)
    {
        $kit = self::find($vendorId, $cooperativeId, $kitId);
        if ($kit === null) {
            return null;
        }
        $items = self::items($vendorId, [(int) $kit['id']])[(int) $kit['id']] ?? [];
        $data = self::summary($kit, $items);
        $data['items'] = $items;
        return $data;
    }

    private static function summary(array $kit, array $items)
    {
        $total = 0;
        $mandatory = 0;
        foreach ($items as $item) {
            if (!$item['is_active']) {
                continue;
            }
            $amount = $item['unit_price'] * $item['qty'];
            $total += $amount;
            if ($item['is_mandatory']) {
                $mandatory += $amount;
            }
        }
        return [
            'id'               => (int) $kit['id'],
            'grade_level_code' => $kit['grade_level_code'],
            'school_year'      => (int) $kit['school_year'],
            'name'             => (string) $kit['name'],
            'note'             => (string) $kit['note'],
            'is_published'     => (bool) $kit['is_published'],
            'item_count'       => count($items),
            'total_price'      => $total,
            'mandatory_price'  => $mandatory,
        ];
    }

    // [kit_id => [item]] — harga semasa produk + varian; is_active = produk (dan varian dipilih) masih aktif
    public static function items($vendorId, array $kitIds)
    {
        if (!$kitIds) {
            return [];
        }
        $marks = implode(',', array_fill(0, count($kitIds), '?'));
        $rows = Database::fetchAll(
            "SELECT i.id, i.school_kit_id, i.product_id, i.variant_id, i.qty, i.is_mandatory, i.note, i.sort_order,
                    p.name, p.price, p.has_variants, p.is_active AS product_active, v.label, v.price_delta, v.is_active AS variant_active
               FROM school_kit_items i
               JOIN products p ON p.vendor_id = i.vendor_id AND p.id = i.product_id
               LEFT JOIN product_variants v ON v.vendor_id = i.vendor_id AND v.product_id = i.product_id AND v.id = i.variant_id
              WHERE i.vendor_id = ? AND i.school_kit_id IN ($marks)
              ORDER BY i.sort_order, i.id",
            array_merge([(int) $vendorId], array_map('intval', $kitIds))
        );
        $result = [];
        foreach ($rows as $row) {
            $hasVariant = $row['variant_id'] !== null;
            $result[(int) $row['school_kit_id']][] = [
                'id'            => (int) $row['id'],
                'product_id'    => (int) $row['product_id'],
                'variant_id'    => $hasVariant ? (int) $row['variant_id'] : null,
                'product_name'  => $row['name'],
                'variant_label' => $hasVariant ? $row['label'] : null,
                'has_variants'  => (bool) $row['has_variants'],
                'unit_price'    => max(0, (int) $row['price'] + ($hasVariant ? (int) $row['price_delta'] : 0)),
                'qty'           => (int) $row['qty'],
                'is_mandatory'  => (bool) $row['is_mandatory'],
                'note'          => (string) $row['note'],
                'sort_order'    => (int) $row['sort_order'],
                'is_active'     => (bool) $row['product_active'] && (!$hasVariant || (bool) $row['variant_active']),
            ];
        }
        return $result;
    }
}
