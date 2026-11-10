<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\AdminAction;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AdminAction::class)]
final class AdminActionTest extends TestCase
{
    #[Test]
    public function constructor(): void
    {
        $adminAction = new AdminAction(
            'truc',
            'machin',
            'ok',
            'content',
            '',
        );

        $this->assertSame('truc', $adminAction->action);
        $this->assertSame('machin', $adminAction->item);
        $this->assertSame('ok', $adminAction->state);
        $this->assertSame('content', $adminAction->content);
        $this->assertSame('', $adminAction->error);
    }
}
