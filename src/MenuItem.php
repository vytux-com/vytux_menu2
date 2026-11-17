<?php

declare(strict_types=1);

namespace Vytux\WebtreesModules\VytuxMenu2;

use function in_array;
use function preg_match;

/**
 * One configurable link in the menu.
 */
final class MenuItem
{
    /**
     * @param list<string> $languages Language tags.  Empty means all languages.
     * @param list<string> $icon_keys Keys of the uploaded icons (see IconSet).
     */
    public function __construct(
        public readonly int $id,
        public readonly int|null $tree_id,
        public readonly int $order,
        public readonly string $title,
        public readonly string $url,
        public readonly int $access_level,
        public readonly array $languages = [],
        public readonly array $icon_keys = [],
    ) {
    }

    /**
     * @param int $user_access_level The user's access level for the tree, from Auth::accessLevel()
     */
    public function isVisibleTo(string $language, int $user_access_level): bool
    {
        if ($this->languages !== [] && !in_array($language, $this->languages, true)) {
            return false;
        }

        return $this->access_level >= $user_access_level;
    }

    /**
     * Reject addresses that a browser could run as script.  Browsers ignore control
     * characters such as tabs and newlines inside a URL ("java\tscript:"), so reject them too.
     */
    public static function isSafeUrl(string $url): bool
    {
        if (preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
            return false;
        }

        return preg_match('/^\s*(javascript|data|vbscript):/i', $url) !== 1;
    }
}
