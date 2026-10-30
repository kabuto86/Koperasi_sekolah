<?php
// Senarai terjemahan owner (PELAN 18.7 Translation): kunci × 4 bahasa, tapis q / group / missing ('' | any | kod bahasa).
class ListTranslationsJob
{
    private $input;

    public function __construct(array $input)
    {
        $this->input = $input;
    }

    public function run()
    {
        $table = TranslationValues::all();
        $q = mb_strtolower(trim((string) ($this->input['q'] ?? '')), 'UTF-8');
        $group = (string) ($this->input['group'] ?? '');
        $missing = (string) ($this->input['missing'] ?? '');

        $rows = [];
        $groups = [];
        foreach ($table as $key => $values) {
            $row = self::shape($key, $values);
            $groups[$row['group']] = true;
            if ($this->matches($row, $q, $group, $missing)) {
                $rows[] = $row;
            }
        }

        list($page, $perPage, $offset) = Pagination::from($this->input);
        $data = Pagination::result(array_slice($rows, $offset, $perPage), count($rows), $page, $perPage);
        $groups = array_keys($groups);
        sort($groups);
        $data['groups'] = $groups;
        $data['missing_counts'] = (new MissingKeysJob($table))->run()['counts'];
        return $data;
    }

    public static function shape($key, array $values)
    {
        return ['key' => $key, 'group' => self::groupOf($key), 'values' => $values, 'missing' => MissingKeysJob::missingIn($values)];
    }

    public static function groupOf($key)
    {
        return explode('.', $key, 2)[0];
    }

    private function matches(array $row, $q, $group, $missing)
    {
        if ($group !== '' && $row['group'] !== $group) {
            return false;
        }
        if ($missing === 'any' && count($row['missing']) === 0) {
            return false;
        }
        if ($missing !== '' && $missing !== 'any' && !in_array($missing, $row['missing'], true)) {
            return false;
        }
        if ($q === '') {
            return true;
        }
        $haystack = mb_strtolower($row['key'] . "\n" . implode("\n", $row['values']), 'UTF-8');
        return mb_strpos($haystack, $q, 0, 'UTF-8') !== false;
    }
}
