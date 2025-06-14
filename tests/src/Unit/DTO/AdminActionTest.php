<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\AdminAction;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AdminAction::class)]
class AdminActionTest extends TestCase
{
    public function testConstructor(): void
    {
        $adminAction = new AdminAction(
            'truc',
            'machin',
            'ok',
            'content',
            '',
        );

        $this->assertEquals('truc', $adminAction->action);
        $this->assertEquals('machin', $adminAction->item);
        $this->assertEquals('ok', $adminAction->state);
        $this->assertEquals('content', $adminAction->content);
        $this->assertEquals('', $adminAction->error);
    }
}
