<?php
// Semua koperasi semua vendor — baca sahaja untuk owner (PELAN 18.7 CooperativeSummary).
// Tapis: q, vendor_id, status, school_type. vendor_id di sini ialah TAPISAN owner, bukan skop keselamatan vendor.
class ListAllCooperativesJob
{
    const STATUSES = ['active', 'suspended', 'closed'];
    const SCHOOL_TYPES = ['SK', 'SJKC', 'SJKT', 'SMK', 'SMKA', 'SBP', 'other'];

    private $input;

    public function __construct(array $input)
    {
        $this->input = $input;
    }

    public function run()
    {
        list($page, $perPage, $offset) = Pagination::from($this->input);
        $where = [];
        $params = [];

        $vendorId = (int) ($this->input['vendor_id'] ?? 0);
        if ($vendorId > 0) {
            $where[] = 'c.vendor_id = ?';
            $params[] = $vendorId;
        }
        $status = (string) ($this->input['status'] ?? '');
        if (in_array($status, self::STATUSES, true)) {
            $where[] = 'c.status = ?';
            $params[] = $status;
        }
        $schoolType = (string) ($this->input['school_type'] ?? '');
        if (in_array($schoolType, self::SCHOOL_TYPES, true)) {
            $where[] = 'c.school_type = ?';
            $params[] = $schoolType;
        }
        $q = trim((string) ($this->input['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(c.school_name LIKE ? OR c.school_code LIKE ? OR c.slug LIKE ? OR v.code LIKE ? OR v.company_name LIKE ?
                         OR c.district LIKE ? OR c.state LIKE ?)';
            $params = array_merge($params, array_fill(0, 7, Pagination::like($q)));
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $from = " FROM cooperatives c JOIN vendors v ON v.id = c.vendor_id JOIN templates t ON t.id = c.template_id";

        $total = Database::fetchValue("SELECT COUNT(*)" . $from . $whereSql, $params);
        $rows = Database::fetchAll(
            "SELECT c.*, v.code AS vendor_code, v.company_name AS vendor_name, t.code AS template_code, t.name AS template_name"
            . $from . $whereSql . " ORDER BY c.id LIMIT $perPage OFFSET $offset",
            $params
        );
        $modules = $this->modules(array_column($rows, 'id'));

        $items = array_map(function ($row) use ($modules) {
            return [
                'id'            => (int) $row['id'],
                'vendor_id'     => (int) $row['vendor_id'],
                'vendor_code'   => $row['vendor_code'],
                'vendor_name'   => $row['vendor_name'],
                'slug'          => $row['slug'],
                'school_name'   => $row['school_name'],
                'school_code'   => $row['school_code'],
                'school_type'   => $row['school_type'],
                'state'         => $row['state'],
                'district'      => $row['district'],
                'status'        => $row['status'],
                'template_code' => $row['template_code'],
                'template_name' => $row['template_name'],
                'modules'       => $modules[$row['id']] ?? [],
                'created_at'    => IsoDate::format($row['created_at']),
            ];
        }, $rows);
        return Pagination::result($items, $total, $page, $perPage);
    }

    // [cooperative_id => ['news', ...]] — modul aktif sahaja
    private function modules(array $cooperativeIds)
    {
        if (count($cooperativeIds) === 0) {
            return [];
        }
        $rows = Database::fetchAll(
            "SELECT cm.cooperative_id, m.code FROM cooperative_modules cm JOIN modules m ON m.id = cm.module_id
              WHERE cm.status = 'active' AND cm.cooperative_id IN (" . implode(',', array_fill(0, count($cooperativeIds), '?')) . ")
              ORDER BY m.sort_order",
            array_map('intval', $cooperativeIds)
        );
        $result = [];
        foreach ($rows as $row) {
            $result[$row['cooperative_id']][] = $row['code'];
        }
        return $result;
    }
}
