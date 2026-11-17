<?php

declare(strict_types=1);

namespace Vytux\WebtreesModules\VytuxMenu2;

use function array_keys;
use function base64_decode;
use function base64_encode;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function ksort;
use function preg_match;

/**
 * The icons for one menu entry: an optional default icon, plus optional per-theme overrides.
 *
 * Stored in the database as JSON: {"<key>": {"mime": "image/png", "data": "<base64>"}, ...}
 * where <key> is DEFAULT_KEY or a theme name.
 */
final class IconSet
{
    public const string DEFAULT_KEY = '*';

    /**
     * @param array<string,array{mime:string,data:string}> $icons
     */
    private function __construct(private readonly array $icons)
    {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public static function fromJson(string $json): self
    {
        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return self::empty();
        }

        $icons = [];

        foreach ($decoded as $key => $icon) {
            if (
                is_string($key) &&
                self::isValidKey($key) &&
                is_array($icon) &&
                is_string($icon['mime'] ?? null) &&
                is_string($icon['data'] ?? null) &&
                IconValidator::isAllowedMimeType($icon['mime'])
            ) {
                $icons[$key] = ['mime' => $icon['mime'], 'data' => $icon['data']];
            }
        }

        return new self($icons);
    }

    public static function isValidKey(string $key): bool
    {
        return $key === self::DEFAULT_KEY || preg_match('/^[A-Za-z0-9_-]{1,32}$/', $key) === 1;
    }

    public function toJson(): string
    {
        return $this->icons === [] ? '' : (string) json_encode($this->icons);
    }

    public function with(string $key, string $mime, string $bytes): self
    {
        $icons       = $this->icons;
        $icons[$key] = ['mime' => $mime, 'data' => base64_encode($bytes)];
        ksort($icons);

        return new self($icons);
    }

    public function without(string $key): self
    {
        $icons = $this->icons;
        unset($icons[$key]);

        return new self($icons);
    }

    public function has(string $key): bool
    {
        return isset($this->icons[$key]);
    }

    /**
     * @return array{mime:string,bytes:string}|null
     */
    public function get(string $key): array|null
    {
        if (!isset($this->icons[$key])) {
            return null;
        }

        return [
            'mime'  => $this->icons[$key]['mime'],
            'bytes' => (string) base64_decode($this->icons[$key]['data'], true),
        ];
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->icons);
    }

    public function isEmpty(): bool
    {
        return $this->icons === [];
    }
}
