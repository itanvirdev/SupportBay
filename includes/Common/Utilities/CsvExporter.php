<?php

declare(strict_types=1);

namespace SupportBay\Common\Utilities;

use RuntimeException;

final class CsvExporter {
  /**
   * @param array<int, array{name: string, headers: string[], rows: array<int, array<int, float|int|string>>}> $sections
   */
  public function generate(array $sections): string {
    $stream = fopen('php://temp', 'w+');
    if ($stream === false) {
      throw new RuntimeException('Report export could not be created.');
    }

    fwrite($stream, "\xEF\xBB\xBF");
    foreach ($sections as $index => $section) {
      if ($index > 0) { fputcsv($stream, [], ',', '"', ''); }
      fputcsv($stream, [$this->safe($section['name'])], ',', '"', '');
      fputcsv($stream, array_map([$this, 'safe'], $section['headers']), ',', '"', '');
      foreach ($section['rows'] as $row) {
        fputcsv($stream, array_map([$this, 'safe'], $row), ',', '"', '');
      }
    }

    rewind($stream);
    $csv = stream_get_contents($stream);
    fclose($stream);
    if ($csv === false) {
      throw new RuntimeException('Report export could not be read.');
    }

    return $csv;
  }

  private function safe(float|int|string $value): string {
    $value = (string) $value;

    return preg_match('/^[=+\-@\t\r]/', $value) === 1
      ? "'" . $value
      : $value;
  }
}
