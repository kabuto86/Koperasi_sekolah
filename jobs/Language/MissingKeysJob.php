<?php
// Kunci terjemahan yang kosong bagi setiap bahasa (fail lang/*.php DAN jadual translations) — PELAN 12.
// Kunci kosong dipapar dalam Bahasa Melayu (jatuh balik), jadi ini senarai kerja penterjemah, bukan ralat paparan.
//   (new MissingKeysJob())->run()  → [ 'counts' => [ ms => 0, en => 3, … ], 'keys' => [ en => ['a.b', …], … ] ]
class MissingKeysJob
{
    private $table;

    // $table = TranslationValues::all() jika pemanggil sudah memuatnya
    public function __construct(array $table = null)
    {
        $this->table = $table;
    }

    public function run()
    {
        $table = $this->table ?? TranslationValues::all();
        $keys = [];
        foreach (Config::get('languages') as $code) {
            $keys[$code] = [];
        }
        foreach ($table as $key => $values) {
            foreach (self::missingIn($values) as $code) {
                $keys[$code][] = $key;
            }
        }
        return ['counts' => array_map('count', $keys), 'keys' => $keys];
    }

    // Kod bahasa yang nilainya kosong untuk satu baris
    public static function missingIn(array $values)
    {
        $missing = [];
        foreach (Config::get('languages') as $code) {
            if (trim((string) ($values[$code] ?? '')) === '') {
                $missing[] = $code;
            }
        }
        return $missing;
    }
}
