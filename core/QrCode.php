<?php
// Penjana QR kecil untuk e-mel (resit ibu bapa — PELAN 6). Port sebahagian assets/lib/qrcode/qrcode.js
// (Kazuhiko Arase, lesen MIT): mod Byte, pembetulan ralat M, versi 1–6 (cukup untuk token 32 aksara).
// Halaman web terus guna versi JS; PHP hanya untuk e-mel (jadual HTML, tanpa imej luar).
//
//   $matrix = QrCode::matrix('a1b2c3...');   // [baris][lajur] => true (gelap) / false
//   $html   = QrCode::emailTable('a1b2c3...');
class QrCode
{
    // [jumlah kod, kod data] setiap blok RS, pembetulan ralat M (versi 1–6)
    const RS_BLOCKS_M = [
        1 => [[1, 26, 16]],
        2 => [[1, 44, 28]],
        3 => [[1, 70, 44]],
        4 => [[2, 50, 32]],
        5 => [[2, 67, 43]],
        6 => [[4, 43, 27]],
    ];

    const PATTERN_POSITIONS = [1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30], 6 => [6, 34]];

    // Tahap M = 0 dalam maklumat format
    const LEVEL_M = 0;

    private static $exp = null;
    private static $log = null;

    private $type;
    private $count;
    private $modules;
    private $data;

    // Matriks QR (versi terkecil yang muat, topeng terbaik)
    public static function matrix($text)
    {
        $bytes = array_values(unpack('C*', (string) $text) ?: []);
        foreach (self::RS_BLOCKS_M as $type => $blocks) {
            if (4 + 8 + 8 * count($bytes) <= self::dataCount($type) * 8) {
                $qr = new self($type, $bytes);
                return $qr->modules;
            }
        }
        throw new InvalidArgumentException('Teks terlalu panjang untuk QR versi 1–6.');
    }

    // Jadual HTML untuk e-mel (atribut bgcolor sahaja — pelanggan e-mel tidak memuat imej luar / CSS)
    public static function emailTable($text, $cell = 4)
    {
        $matrix = self::matrix($text);
        $size = count($matrix);
        $html = '<table cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff"><tr><td colspan="' . ($size + 2) . '" height="' . ($cell * 2) . '"></td></tr>';
        foreach ($matrix as $row) {
            $html .= '<tr><td width="' . ($cell * 2) . '" height="' . $cell . '"></td>';
            foreach ($row as $dark) {
                $html .= '<td width="' . $cell . '" height="' . $cell . '" bgcolor="' . ($dark ? '#000000' : '#ffffff') . '"></td>';
            }
            $html .= '<td width="' . ($cell * 2) . '"></td></tr>';
        }
        return $html . '<tr><td colspan="' . ($size + 2) . '" height="' . ($cell * 2) . '"></td></tr></table>';
    }

    private function __construct($type, array $bytes)
    {
        $this->type = $type;
        $this->count = $type * 4 + 17;
        $this->data = $this->createData($bytes);
        $best = 0;
        $lowest = null;
        for ($mask = 0; $mask < 8; $mask++) {
            $this->make(true, $mask);
            $lost = $this->lostPoint();
            if ($lowest === null || $lost < $lowest) {
                $lowest = $lost;
                $best = $mask;
            }
        }
        $this->make(false, $best);
    }

    private static function dataCount($type)
    {
        $total = 0;
        foreach (self::RS_BLOCKS_M[$type] as $block) {
            $total += $block[0] * $block[2];
        }
        return $total;
    }

    private function make($test, $mask)
    {
        $this->modules = array_fill(0, $this->count, array_fill(0, $this->count, null));
        $this->probe(0, 0);
        $this->probe($this->count - 7, 0);
        $this->probe(0, $this->count - 7);
        $this->adjustPatterns();
        $this->timing();
        $this->typeInfo($test, $mask);
        $this->mapData($mask);
    }

    private function probe($row, $col)
    {
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                if ($row + $r < 0 || $row + $r >= $this->count || $col + $c < 0 || $col + $c >= $this->count) {
                    continue;
                }
                $this->modules[$row + $r][$col + $c] = ($r >= 0 && $r <= 6 && ($c === 0 || $c === 6))
                    || ($c >= 0 && $c <= 6 && ($r === 0 || $r === 6))
                    || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4);
            }
        }
    }

    private function adjustPatterns()
    {
        $positions = self::PATTERN_POSITIONS[$this->type];
        foreach ($positions as $row) {
            foreach ($positions as $col) {
                if ($this->modules[$row][$col] !== null) {
                    continue;
                }
                for ($r = -2; $r <= 2; $r++) {
                    for ($c = -2; $c <= 2; $c++) {
                        $this->modules[$row + $r][$col + $c] = $r === -2 || $r === 2 || $c === -2 || $c === 2 || ($r === 0 && $c === 0);
                    }
                }
            }
        }
    }

    private function timing()
    {
        for ($i = 8; $i < $this->count - 8; $i++) {
            if ($this->modules[$i][6] === null) {
                $this->modules[$i][6] = $i % 2 === 0;
            }
            if ($this->modules[6][$i] === null) {
                $this->modules[6][$i] = $i % 2 === 0;
            }
        }
    }

    private static function bchDigit($data)
    {
        $digit = 0;
        while ($data !== 0) {
            $digit++;
            $data >>= 1;
        }
        return $digit;
    }

    private static function bchTypeInfo($data)
    {
        $g15 = (1 << 10) | (1 << 8) | (1 << 5) | (1 << 4) | (1 << 2) | (1 << 1) | 1;
        $d = $data << 10;
        while (self::bchDigit($d) - self::bchDigit($g15) >= 0) {
            $d ^= $g15 << (self::bchDigit($d) - self::bchDigit($g15));
        }
        return (($data << 10) | $d) ^ ((1 << 14) | (1 << 12) | (1 << 10) | (1 << 4) | (1 << 1));
    }

    private function typeInfo($test, $mask)
    {
        $bits = self::bchTypeInfo((self::LEVEL_M << 3) | $mask);
        for ($i = 0; $i < 15; $i++) {
            $dark = !$test && (($bits >> $i) & 1) === 1;
            if ($i < 6) {
                $this->modules[$i][8] = $dark;
            } elseif ($i < 8) {
                $this->modules[$i + 1][8] = $dark;
            } else {
                $this->modules[$this->count - 15 + $i][8] = $dark;
            }
            if ($i < 8) {
                $this->modules[8][$this->count - $i - 1] = $dark;
            } elseif ($i < 9) {
                $this->modules[8][15 - $i] = $dark;
            } else {
                $this->modules[8][15 - $i - 1] = $dark;
            }
        }
        $this->modules[$this->count - 8][8] = !$test;
    }

    private static function maskBit($mask, $i, $j)
    {
        switch ($mask) {
            case 0: return ($i + $j) % 2 === 0;
            case 1: return $i % 2 === 0;
            case 2: return $j % 3 === 0;
            case 3: return ($i + $j) % 3 === 0;
            case 4: return (intdiv($i, 2) + intdiv($j, 3)) % 2 === 0;
            case 5: return ($i * $j) % 2 + ($i * $j) % 3 === 0;
            case 6: return (($i * $j) % 2 + ($i * $j) % 3) % 2 === 0;
            default: return (($i * $j) % 3 + ($i + $j) % 2) % 2 === 0;
        }
    }

    private function mapData($mask)
    {
        $inc = -1;
        $row = $this->count - 1;
        $bitIndex = 7;
        $byteIndex = 0;
        for ($col = $this->count - 1; $col > 0; $col -= 2) {
            if ($col === 6) {
                $col--;
            }
            while (true) {
                for ($c = 0; $c < 2; $c++) {
                    if ($this->modules[$row][$col - $c] !== null) {
                        continue;
                    }
                    $dark = $byteIndex < count($this->data) && (($this->data[$byteIndex] >> $bitIndex) & 1) === 1;
                    if (self::maskBit($mask, $row, $col - $c)) {
                        $dark = !$dark;
                    }
                    $this->modules[$row][$col - $c] = $dark;
                    $bitIndex--;
                    if ($bitIndex === -1) {
                        $byteIndex++;
                        $bitIndex = 7;
                    }
                }
                $row += $inc;
                if ($row < 0 || $row >= $this->count) {
                    $row -= $inc;
                    $inc = -$inc;
                    break;
                }
            }
        }
    }

    // Bit: mod (4) + panjang (8) + data + penamat + pad, kemudian kod pembetulan ralat RS diselang-seli
    private function createData(array $bytes)
    {
        $bits = [];
        $put = function ($num, $length) use (&$bits) {
            for ($i = $length - 1; $i >= 0; $i--) {
                $bits[] = ($num >> $i) & 1;
            }
        };
        $put(4, 4);
        $put(count($bytes), 8);
        foreach ($bytes as $byte) {
            $put($byte, 8);
        }
        $capacity = self::dataCount($this->type) * 8;
        if (count($bits) + 4 <= $capacity) {
            $put(0, 4);
        }
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }
        $pads = [0xEC, 0x11];
        for ($p = 0; count($bits) < $capacity; $p++) {
            $put($pads[$p % 2], 8);
        }
        $buffer = [];
        foreach (array_chunk($bits, 8) as $chunk) {
            $buffer[] = bindec(implode('', $chunk));
        }

        $dcData = [];
        $ecData = [];
        $offset = 0;
        foreach (self::RS_BLOCKS_M[$this->type] as $block) {
            for ($n = 0; $n < $block[0]; $n++) {
                $dc = array_slice($buffer, $offset, $block[2]);
                $offset += $block[2];
                $ecCount = $block[1] - $block[2];
                $dcData[] = $dc;
                $ecData[] = self::ecBytes($dc, $ecCount);
            }
        }
        $result = [];
        foreach ([$dcData, $ecData] as $groups) {
            $max = max(array_map('count', $groups));
            for ($i = 0; $i < $max; $i++) {
                foreach ($groups as $group) {
                    if ($i < count($group)) {
                        $result[] = $group[$i];
                    }
                }
            }
        }
        return $result;
    }

    // Baki pembahagian polinomial data dengan polinomial penjana (GF(256))
    private static function ecBytes(array $data, $ecCount)
    {
        self::initTables();
        $generator = [1];
        for ($i = 0; $i < $ecCount; $i++) {
            $next = array_fill(0, count($generator) + 1, 0);
            foreach ($generator as $j => $coef) {
                $next[$j] ^= $coef;
                $next[$j + 1] ^= self::gmul($coef, self::$exp[$i]);
            }
            $generator = $next;
        }
        $remainder = array_merge($data, array_fill(0, $ecCount, 0));
        for ($i = 0; $i < count($data); $i++) {
            $factor = $remainder[$i];
            if ($factor === 0) {
                continue;
            }
            foreach ($generator as $j => $coef) {
                $remainder[$i + $j] ^= self::gmul($coef, $factor);
            }
        }
        return array_slice($remainder, count($data));
    }

    private static function initTables()
    {
        if (self::$exp !== null) {
            return;
        }
        self::$exp = [];
        self::$log = [];
        for ($i = 0; $i < 8; $i++) {
            self::$exp[$i] = 1 << $i;
        }
        for ($i = 8; $i < 256; $i++) {
            self::$exp[$i] = self::$exp[$i - 4] ^ self::$exp[$i - 5] ^ self::$exp[$i - 6] ^ self::$exp[$i - 8];
        }
        for ($i = 0; $i < 255; $i++) {
            self::$log[self::$exp[$i]] = $i;
        }
    }

    private static function gmul($a, $b)
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }
        return self::$exp[(self::$log[$a] + self::$log[$b]) % 255];
    }

    // Skor penalti topeng (sama seperti QRUtil.getLostPoint)
    private function lostPoint()
    {
        $n = $this->count;
        $m = $this->modules;
        $lost = 0;
        for ($row = 0; $row < $n; $row++) {
            for ($col = 0; $col < $n; $col++) {
                $same = 0;
                for ($r = -1; $r <= 1; $r++) {
                    for ($c = -1; $c <= 1; $c++) {
                        if (($r === 0 && $c === 0) || $row + $r < 0 || $row + $r >= $n || $col + $c < 0 || $col + $c >= $n) {
                            continue;
                        }
                        if ($m[$row][$col] === $m[$row + $r][$col + $c]) {
                            $same++;
                        }
                    }
                }
                if ($same > 5) {
                    $lost += 3 + $same - 5;
                }
            }
        }
        for ($row = 0; $row < $n - 1; $row++) {
            for ($col = 0; $col < $n - 1; $col++) {
                $count = (int) $m[$row][$col] + (int) $m[$row + 1][$col] + (int) $m[$row][$col + 1] + (int) $m[$row + 1][$col + 1];
                if ($count === 0 || $count === 4) {
                    $lost += 3;
                }
            }
        }
        $pattern = [true, false, true, true, true, false, true];
        for ($a = 0; $a < $n; $a++) {
            for ($b = 0; $b < $n - 6; $b++) {
                $rowMatch = true;
                $colMatch = true;
                for ($k = 0; $k < 7; $k++) {
                    $rowMatch = $rowMatch && $m[$a][$b + $k] === $pattern[$k];
                    $colMatch = $colMatch && $m[$b + $k][$a] === $pattern[$k];
                }
                $lost += ($rowMatch ? 40 : 0) + ($colMatch ? 40 : 0);
            }
        }
        $dark = 0;
        foreach ($m as $line) {
            $dark += count(array_filter($line));
        }
        return $lost + abs(100 * $dark / $n / $n - 50) / 5 * 10;
    }
}
