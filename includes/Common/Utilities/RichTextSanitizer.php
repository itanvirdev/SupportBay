<?php

declare(strict_types=1);

namespace SupportBay\Common\Utilities;

final class RichTextSanitizer {
  public static function sanitize(string $content): string {
    $content = preg_replace_callback(
      '/\sstyle=("|\')(.*?)\1/is',
      static function (array $matches): string {
        $allowed = [];

        foreach (explode(';', $matches[2]) as $declaration) {
          [$property, $value] = array_pad(explode(':', $declaration, 2), 2, '');
          $property = strtolower(trim($property));
          $value = strtolower(trim($value));

          if ($property === 'text-align' && in_array($value, ['left', 'center', 'right'], true)) {
            $allowed[] = 'text-align: ' . $value;
          }

          if ($property === 'color' && preg_match('/^(#[0-9a-f]{3,6}|rgb\([0-9, ]+\)|black|gray|red|blue|green)$/', $value)) {
            $allowed[] = 'color: ' . $value;
          }
        }

        return $allowed ? ' style="' . esc_attr(implode('; ', $allowed)) . '"' : '';
      },
      $content,
    ) ?? '';

    return trim(wp_kses($content, self::allowedHtml(), ['http', 'https', 'mailto']));
  }

  /** @return array<string, array<string, bool>> */
  private static function allowedHtml(): array {
    return [
      'p' => ['style' => true], 'br' => [], 'strong' => [], 'b' => [],
      'em' => [], 'i' => [], 'u' => [], 'ul' => [], 'ol' => [], 'li' => [],
      'div' => ['style' => true], 'span' => ['style' => true],
      'a' => ['href' => true, 'target' => true, 'rel' => true],
    ];
  }
}
