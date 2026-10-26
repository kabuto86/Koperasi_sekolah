<?php
// Senarai sekolah untuk "Pilih sekolah" (PELAN 18.12 store/schools/list): koperasi aktif sahaja, tapis q & negeri.
class SearchSchoolsJob
{
    private $input;

    public function __construct(array $input)
    {
        $this->input = $input;
    }

    public function run()
    {
        list($page, $perPage, $offset) = Pagination::from($this->input);
        $marks = implode(',', array_fill(0, count(StoreSchool::OPEN_VENDOR_STATUSES), '?'));
        $where = "c.status = 'active' AND v.status IN ($marks)";
        $args = StoreSchool::OPEN_VENDOR_STATUSES;

        $state = trim((string) ($this->input['state'] ?? ''));
        if ($state !== '') {
            $where .= ' AND c.state = ?';
            $args[] = $state;
        }
        $q = trim((string) ($this->input['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (c.school_name LIKE ? OR c.district LIKE ? OR c.state LIKE ?)';
            $like = Pagination::like($q);
            array_push($args, $like, $like, $like);
        }

        $from = "FROM cooperatives c JOIN vendors v ON v.id = c.vendor_id WHERE $where";
        $total = Database::fetchValue("SELECT COUNT(*) $from", $args);
        $rows = Database::fetchAll(
            "SELECT c.slug, c.school_name, c.school_type, c.state, c.district $from ORDER BY c.school_name LIMIT $perPage OFFSET $offset",
            $args
        );
        return Pagination::result(array_map(['StoreSchool', 'publicShape'], $rows), $total, $page, $perPage);
    }
}
