<?php

namespace Tests\Unit;

use App\Support\OrderFlow;
use PHPUnit\Framework\TestCase;

class OrderFlowTest extends TestCase
{
    public function test_it_allows_valid_status_transitions(): void
    {
        $this->assertTrue(OrderFlow::canMoveTo('pending', 'confirmed'));
        $this->assertTrue(OrderFlow::canMoveTo('processing', 'packed'));
        $this->assertTrue(OrderFlow::canMoveTo('shipped', 'delivered'));
        $this->assertTrue(OrderFlow::canMoveTo('delivered', 'returned'));
    }

    public function test_it_blocks_invalid_status_transitions(): void
    {
        $this->assertFalse(OrderFlow::canMoveTo('pending', 'delivered'));
        $this->assertFalse(OrderFlow::canMoveTo('cancelled', 'processing'));
        $this->assertFalse(OrderFlow::canMoveTo('returned', 'delivered'));
    }

    public function test_admin_can_move_active_orders_directly_to_delivered(): void
    {
        $this->assertTrue(OrderFlow::canAdminMoveTo('pending', 'delivered'));
        $this->assertTrue(OrderFlow::canAdminMoveTo('confirmed', 'delivered'));
        $this->assertTrue(OrderFlow::canAdminMoveTo('processing', 'delivered'));
        $this->assertFalse(OrderFlow::canAdminMoveTo('cancelled', 'delivered'));
        $this->assertFalse(OrderFlow::canAdminMoveTo('returned', 'delivered'));
    }
}
