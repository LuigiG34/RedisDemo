<?php

namespace App\Tests\Unit\Enum;

use App\Enum\QueuePriority;
use PHPUnit\Framework\TestCase;

final class QueuePriorityTest extends TestCase
{

    /**
     * Verify that integers map to the right enum case
     * @return void
     */
    public function testFromIntThresholds(): void
    {
        $this->assertSame(QueuePriority::LOW, QueuePriority::fromInt(0));
        $this->assertSame(QueuePriority::LOW, QueuePriority::fromInt(1));
        $this->assertSame(QueuePriority::NORMAL, QueuePriority::fromInt(2));
        $this->assertSame(QueuePriority::HIGH, QueuePriority::fromInt(3));
        $this->assertSame(QueuePriority::HIGH, QueuePriority::fromInt(99));
    }

    /**
     * Verify each enum case returns the expected transport service name
     * @return void
     */
    public function testTransportMapping(): void
    {
        $this->assertSame('async_low', QueuePriority::LOW->transport());
        $this->assertSame('async', QueuePriority::NORMAL->transport());
        $this->assertSame('async_high', QueuePriority::HIGH->transport());
    }
}
