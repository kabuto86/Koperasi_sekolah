<?php
// Senarai berita koperasi (vendor/news/list — PELAN 18.13). Tapis q (tajuk / isi) & state (dikira pelayan).
class ListNewsJob
{
    const STATES = ['published', 'scheduled', 'draft'];

    private $vendorId;
    private $cooperativeId;
    private $params;

    public function __construct($vendorId, $cooperativeId, array $params)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->params = $params;
    }

    public function run()
    {
        list($page, $perPage, $offset) = Pagination::from($this->params);
        $where = 'vendor_id = ? AND cooperative_id = ?';
        $args = [$this->vendorId, $this->cooperativeId];
        $q = trim((string) ($this->params['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (title LIKE ? OR body LIKE ?)';
            $args[] = Pagination::like($q);
            $args[] = Pagination::like($q);
        }
        $state = (string) ($this->params['state'] ?? '');
        if (in_array($state, self::STATES, true)) {
            $where .= ' AND ' . NewsPosts::STATE_SQL . ' = ?';
            $args[] = $state;
        }
        $total = Database::fetchValue("SELECT COUNT(*) FROM news_posts WHERE $where", $args);
        $rows = Database::fetchAll(
            "SELECT *, " . NewsPosts::STATE_SQL . " AS state FROM news_posts WHERE $where
              ORDER BY published_at IS NULL DESC, published_at DESC, id DESC LIMIT $perPage OFFSET $offset",
            $args
        );
        return Pagination::result(array_map([NewsPosts::class, 'shape'], $rows), $total, $page, $perPage);
    }
}
