<?php

// Validasi panjang karakter string
function validasi_panjang(string $val, int $min, int $max): bool {
    $len = mb_strlen(trim($val), 'UTF-8');
    return $len >= $min && $len <= $max;
}

// Validasi nilai string terhadap daftar putih (whitelist)
function validasi_whitelist(string $val, array $allowed): bool {
    return in_array($val, $allowed, true);
}

// Validasi angka minimal
function validasi_angka_min($val, float $min): bool {
    return is_numeric($val) && (float)$val >= $min;
}

// Validasi rentang bilangan bulat
function validasi_rentang_int($val, int $min, int $max): bool {
    if (filter_var($val, FILTER_VALIDATE_INT) === false) {
        return false;
    }
    $v = (int)$val;
    return $v >= $min && $v <= $max;
}

function validasi_tanggal_mulai(string $tanggal, ?string $tanggal_lama = null): bool {
    $d = DateTime::createFromFormat('Y-m-d', $tanggal);
    if (!$d || $d->format('Y-m-d') !== $tanggal) {
        return false;
    }

    if ($tanggal_lama !== null && $tanggal === $tanggal_lama) {
        return true;
    }

    $today = new DateTime('today');
    return $d >= $today;
}
