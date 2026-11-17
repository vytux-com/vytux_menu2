<?php

declare(strict_types=1);

namespace Vytux\WebtreesModules\VytuxMenu2\Tests;

use PHPUnit\Framework\TestCase;
use Vytux\WebtreesModules\VytuxMenu2\MenuItem;
use Vytux\WebtreesModules\VytuxMenu2\MenuRepository;

final class MenuRepositoryTest extends TestCase
{
    public function testMoveSkipsItemsHiddenByTheTreeFilter(): void
    {
        $all = [$this->item(1, 1, 1), $this->item(2, 2, 2), $this->item(3, 1, 3)];

        // Moving item 3 up within tree 1 swaps it with item 1, not the hidden item 2.
        self::assertSame([3 => 1, 1 => 3], MenuRepository::reorder($all, [1, 3], 3, -1));
    }

    public function testMoveWithSharedPositionsMovesOnePlace(): void
    {
        $all = [$this->item(1, null, 1), $this->item(2, null, 1), $this->item(3, null, 1)];

        // Order becomes 2, 1, 3. Item 2 keeps position 1.
        self::assertSame([1 => 2, 3 => 3], MenuRepository::reorder($all, [1, 2, 3], 1, 1));
    }

    public function testMoveBeyondTheEndDoesNothing(): void
    {
        $all = [$this->item(1, null, 1), $this->item(2, null, 2)];

        self::assertSame([], MenuRepository::reorder($all, [1, 2], 2, 1));
        self::assertSame([], MenuRepository::reorder($all, [1, 2], 1, -1));
    }

    private function item(int $id, int|null $tree_id, int $order): MenuItem
    {
        return new MenuItem($id, $tree_id, $order, 'Item ' . $id, '/', 2);
    }
}
