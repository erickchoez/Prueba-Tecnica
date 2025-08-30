<?php
namespace App\Helpers;

final class Http {
  public static function intOrNull(mixed $v): ?int {
    return (isset($v) && $v !== '') ? (int)$v : null;
  }
  public static function strOrNull(mixed $v): ?string {
    $s = is_string($v) ? trim($v) : null;
    return ($s === '') ? null : $s;
  }
  public static function moneyOrFail(mixed $v): float {
    if (!is_numeric($v) || $v < 0) throw new \InvalidArgumentException('Invalid money');
    return round((float)$v, 2);
  }
}
