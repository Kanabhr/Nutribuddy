<?php

namespace Tests\Feature;

use App\Jobs\CancelShiprocketOrder;
use App\Models\Order;
use App\Models\User;
use App\Services\Shipping\ShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ShiprocketShipmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Orders dispatch a Shiprocket push when created or paid; never run it against the real API in tests.
        Queue::fake();
        Cache::forget('shiprocket_token');
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();

        config()->set('services.shiprocket.email', 'api@example.com');
        config()->set('services.shiprocket.password', 'secret');
        config()->set('services.shiprocket.webhook_token', 'hook-token');
    }

    public function test_assign_awb_saves_awb_courier_and_history(): void
    {
        Http::fake([
            '*/auth/login' => Http::response(['token' => 'tok']),
            '*/courier/assign/awb' => Http::response([
                'awb_assign_status' => 1,
                'response' => ['data' => ['awb_code' => 'AWB123', 'courier_name' => 'Delhivery', 'courier_company_id' => 142]],
            ]),
        ]);
        $order = $this->shippedOrder();

        app(ShipmentService::class)->assignAwb($order);

        $order->refresh();
        $this->assertSame('AWB123', $order->awb_code);
        $this->assertSame('Delhivery', $order->courier_name);
        $this->assertDatabaseHas('order_shipment_events', ['order_id' => $order->id, 'status' => 'AWB ASSIGNED']);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/courier/assign/awb') && $request['shipment_id'] === 800200);
    }

    public function test_failed_awb_assignment_changes_nothing_and_shows_the_reason(): void
    {
        Http::fake([
            '*/auth/login' => Http::response(['token' => 'tok']),
            '*/courier/assign/awb' => Http::response(['awb_assign_status' => 0, 'message' => 'Insufficient wallet balance.']),
        ]);
        $order = $this->shippedOrder();

        try {
            app(ShipmentService::class)->assignAwb($order);
            $this->fail('Expected a RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Insufficient wallet balance', $e->getMessage());
        }

        $this->assertNull($order->fresh()->awb_code);
        $this->assertDatabaseCount('order_shipment_events', 0);
    }

    public function test_awb_cannot_be_assigned_twice(): void
    {
        Http::fake();
        $order = $this->shippedOrder(['awb_code' => 'AWB123']);

        $this->expectException(RuntimeException::class);
        app(ShipmentService::class)->assignAwb($order);
    }

    public function test_label_url_is_saved(): void
    {
        Http::fake([
            '*/auth/login' => Http::response(['token' => 'tok']),
            '*/courier/generate/label' => Http::response(['label_created' => 1, 'label_url' => 'https://example.com/label.pdf']),
        ]);
        $order = $this->shippedOrder(['awb_code' => 'AWB123']);

        app(ShipmentService::class)->generateLabel($order);

        $this->assertSame('https://example.com/label.pdf', $order->fresh()->shiprocket_label_url);
    }

    public function test_tracking_refresh_stores_each_step_once_and_updates_the_status(): void
    {
        Http::fake([
            '*/auth/login' => Http::response(['token' => 'tok']),
            '*/courier/track/awb/*' => Http::response(['tracking_data' => [
                'track_status' => 1,
                'shipment_track' => [['current_status' => 'IN TRANSIT']],
                'shipment_track_activities' => [
                    ['date' => '2026-11-20 09:00:00', 'status' => 'PICKED UP', 'activity' => 'Shipment picked up', 'location' => 'Indore'],
                    ['date' => '2026-11-21 08:30:00', 'status' => 'IN TRANSIT', 'activity' => 'Arrived at hub', 'location' => 'Bhopal'],
                ],
            ]]),
        ]);
        $order = $this->shippedOrder(['awb_code' => 'AWB123']);
        $service = app(ShipmentService::class);

        $this->assertSame(2, $service->refreshTracking($order));
        $this->assertSame(0, $service->refreshTracking($order->fresh()));

        $this->assertDatabaseCount('order_shipment_events', 2);
        $this->assertSame('IN TRANSIT', $order->fresh()->shiprocket_status);
    }

    public function test_webhook_rejects_missing_or_wrong_token(): void
    {
        $this->postJson(route('courier-tracking.webhook'), ['awb' => 'AWB123'])->assertUnauthorized();
        $this->postJson(route('courier-tracking.webhook'), ['awb' => 'AWB123'], ['x-api-key' => 'wrong'])->assertUnauthorized();
    }

    public function test_webhook_is_disabled_until_a_token_is_configured(): void
    {
        config()->set('services.shiprocket.webhook_token', '');

        $this->postJson(route('courier-tracking.webhook'), ['awb' => 'AWB123'], ['x-api-key' => ''])
            ->assertStatus(503);
    }

    public function test_webhook_updates_status_and_history_and_ignores_duplicates(): void
    {
        $order = $this->shippedOrder(['awb_code' => 'AWB123']);
        $payload = [
            'awb' => 'AWB123',
            'courier_name' => 'Delhivery',
            'current_status' => 'OUT FOR DELIVERY',
            'current_timestamp' => '23 11 2026 10:00:00',
            'sr_order_id' => 900100,
            'scans' => [
                ['date' => '2026-11-22 20:00:00', 'status' => 'IN TRANSIT', 'activity' => 'Reached destination hub', 'location' => 'Indore'],
                ['date' => '2026-11-23 10:00:00', 'status' => 'OUT FOR DELIVERY', 'activity' => 'Out for delivery', 'location' => 'Indore'],
            ],
        ];

        $this->postJson(route('courier-tracking.webhook'), $payload, ['x-api-key' => 'hook-token'])->assertOk();
        $this->postJson(route('courier-tracking.webhook'), $payload, ['x-api-key' => 'hook-token'])->assertOk();

        $order->refresh();
        $this->assertSame('OUT FOR DELIVERY', $order->shiprocket_status);
        $this->assertSame('Delhivery', $order->courier_name);
        $this->assertDatabaseCount('order_shipment_events', 2);
    }

    public function test_an_older_webhook_does_not_overwrite_a_newer_status(): void
    {
        $order = $this->shippedOrder(['awb_code' => 'AWB123']);

        $this->postJson(route('courier-tracking.webhook'), [
            'awb' => 'AWB123', 'current_status' => 'OUT FOR DELIVERY', 'current_timestamp' => '23 11 2026 10:00:00',
        ], ['x-api-key' => 'hook-token'])->assertOk();

        $this->postJson(route('courier-tracking.webhook'), [
            'awb' => 'AWB123', 'current_status' => 'IN TRANSIT', 'current_timestamp' => '22 11 2026 08:00:00',
        ], ['x-api-key' => 'hook-token'])->assertOk();

        $this->assertSame('OUT FOR DELIVERY', $order->fresh()->shiprocket_status);
    }

    public function test_webhook_matches_by_shiprocket_order_id_when_the_awb_is_not_stored_yet(): void
    {
        $order = $this->shippedOrder();

        $this->postJson(route('courier-tracking.webhook'), [
            'awb' => 'AWB777', 'sr_order_id' => 900100, 'current_status' => 'PICKED UP', 'current_timestamp' => '22 11 2026 09:00:00',
        ], ['x-api-key' => 'hook-token'])->assertOk();

        $order->refresh();
        $this->assertSame('AWB777', $order->awb_code);
        $this->assertSame('PICKED UP', $order->shiprocket_status);
    }

    public function test_webhook_for_unknown_shipment_or_return_shipment_is_acknowledged_without_changes(): void
    {
        $this->shippedOrder(['awb_code' => 'AWB123']);

        $this->postJson(route('courier-tracking.webhook'), ['awb' => 'NOPE', 'current_status' => 'DELIVERED'], ['x-api-key' => 'hook-token'])->assertOk();
        $this->postJson(route('courier-tracking.webhook'), ['awb' => 'AWB123', 'is_return' => 1, 'current_status' => 'DELIVERED'], ['x-api-key' => 'hook-token'])->assertOk();

        $this->assertDatabaseCount('order_shipment_events', 0);
    }

    public function test_admin_with_manage_orders_can_assign_an_awb(): void
    {
        Http::fake([
            '*/auth/login' => Http::response(['token' => 'tok']),
            '*/courier/assign/awb' => Http::response([
                'awb_assign_status' => 1,
                'response' => ['data' => ['awb_code' => 'AWB555', 'courier_name' => 'Xpressbees']],
            ]),
        ]);
        $order = $this->shippedOrder();

        $this->actingAs($this->adminWith(['view orders', 'manage orders']), 'admin')
            ->post(route('admin.ecommerce.orders.shipping.assign-awb', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('AWB555', $order->fresh()->awb_code);
    }

    public function test_admin_without_manage_orders_cannot_use_shipping_actions(): void
    {
        Http::fake();
        $order = $this->shippedOrder();

        $this->actingAs($this->adminWith(['view orders']), 'admin')
            ->post(route('admin.ecommerce.orders.shipping.assign-awb', $order))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_shipping_errors_are_shown_to_the_admin(): void
    {
        Http::fake();
        $order = $this->shippedOrder(); // no AWB yet

        $this->actingAs($this->adminWith(['view orders', 'manage orders']), 'admin')
            ->post(route('admin.ecommerce.orders.shipping.pickup', $order))
            ->assertRedirect()
            ->assertSessionHas('error', 'Assign an AWB first.');
    }

    public function test_cancelling_an_order_queues_the_shiprocket_cancellation(): void
    {
        // Needs the OrderObserver patch from PASTE-GUIDE.md.
        $order = $this->shippedOrder();

        $order->update(['status' => 'cancelled']);

        Queue::assertPushed(CancelShiprocketOrder::class);
    }

    /** @dataProvider statuses */
    public function test_status_text_is_grouped_into_buckets(?string $status, string $expected): void
    {
        $this->assertSame($expected, ShipmentService::classify($status));
    }

    /** @return array<string, array{0: ?string, 1: string}> */
    public static function statuses(): array
    {
        return [
            'delivered' => ['DELIVERED', 'delivered'],
            'undelivered is not delivered' => ['UNDELIVERED', 'ndr'],
            'rto delivered is not delivered' => ['RTO DELIVERED', 'rto'],
            'rto initiated' => ['RTO INITIATED', 'rto'],
            'out for delivery' => ['OUT FOR DELIVERY', 'out_for_delivery'],
            'in transit' => ['IN TRANSIT', 'in_transit'],
            'picked up' => ['PICKED UP', 'picked_up'],
            'pickup scheduled' => ['PICKUP SCHEDULED', 'pickup_pending'],
            'cancelled' => ['CANCELED', 'cancelled'],
            'empty' => [null, 'unknown'],
        ];
    }

    /** @param array<string, mixed> $attributes */
    private function shippedOrder(array $attributes = []): Order
    {
        $order = Order::factory()->create(['status' => 'confirmed']);
        $order->forceFill(array_merge([
            'shiprocket_order_id' => '900100',
            'shiprocket_shipment_id' => '800200',
        ], $attributes))->save();

        return $order->fresh();
    }

    /** @param list<string> $permissions */
    private function adminWith(array $permissions): User
    {
        foreach (['view orders', 'manage orders'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $role = Role::create(['name' => 'Role '.uniqid(), 'guard_name' => 'admin']);
        $role->givePermissionTo($permissions);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        return $admin;
    }
}
