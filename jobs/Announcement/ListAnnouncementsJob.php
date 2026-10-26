<?php
// Pengumuman owner kepada vendor (PELAN 18.7 Announcement) — terkini dahulu. Tapis: q.
class ListAnnouncementsJob
{
    const SELECT = "SELECT a.*, u.full_name AS created_by_name FROM vendor_announcements a LEFT JOIN platform_users u ON u.id = a.created_by";

    private $input;

    public function __construct(array $input)
    {
        $this->input = $input;
    }

    public function run()
    {
        list($page, $perPage, $offset) = Pagination::from($this->input);
        $whereSql = '';
        $params = [];
        $q = trim((string) ($this->input['q'] ?? ''));
        if ($q !== '') {
            $whereSql = ' WHERE (a.title LIKE ? OR a.body LIKE ?)';
            $params = [Pagination::like($q), Pagination::like($q)];
        }
        $total = Database::fetchValue("SELECT COUNT(*) FROM vendor_announcements a" . $whereSql, $params);
        $rows = Database::fetchAll(self::SELECT . $whereSql . " ORDER BY a.id DESC LIMIT $perPage OFFSET $offset", $params);
        return Pagination::result(self::shapeAll($rows), $total, $page, $perPage);
    }

    public static function find($id)
    {
        $rows = Database::fetchAll(self::SELECT . " WHERE a.id = ?", [(int) $id]);
        return count($rows) ? self::shapeAll($rows)[0] : null;
    }

    private static function shapeAll(array $rows)
    {
        $targets = [];
        if (count($rows)) {
            $ids = array_map('intval', array_column($rows, 'id'));
            $targetRows = Database::fetchAll(
                "SELECT t.announcement_id, v.id, v.code FROM vendor_announcement_targets t JOIN vendors v ON v.id = t.vendor_id
                  WHERE t.announcement_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ") ORDER BY v.code",
                $ids
            );
            foreach ($targetRows as $row) {
                $targets[$row['announcement_id']][] = $row;
            }
        }
        return array_map(function ($row) use ($targets) {
            $selected = $targets[$row['id']] ?? [];
            return [
                'id'           => (int) $row['id'],
                'title'        => $row['title'],
                'body'         => $row['body'],
                'target'       => $row['target'],
                'vendor_ids'   => array_map('intval', array_column($selected, 'id')),
                'vendor_names' => array_column($selected, 'code'),
                'published_at' => IsoDate::format($row['published_at']),
                'created_by'   => (string) $row['created_by_name'],
                'created_at'   => IsoDate::format($row['created_at']),
            ];
        }, $rows);
    }
}
