<?php

namespace Tests\Feature;

use App\Jobs\SendOrderNotification;
use App\Mail\OrderNotificationMail;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Order;
use App\Models\OrderPaymentAdjustment;
use App\Models\User;
use App\Services\Notifications\OrderNotificationService;
use App\Services\Shipping\ShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OrderNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_default_templates_exist_after_migrating(): void
    {
        $this->assertSame(5, NotificationTemplate::where('channel', 'email')->where('enabled', false)->count());
        $this->assertSame(0, NotificationTemplate::where('enabled', true)->count());
        $this->assertSame(6, NotificationTemplate::where('channel', 'whatsapp')->where('enabled', false)->count());
    }

    public function test_order_events_queue_the_right_notification(): void
    {
        Queue::fake();
        $order = $this->order(['status' => 'pending', 'payment_status' => 'paid']);

        Queue::assertPushed(SendOrderNotification::class, fn ($job) => $job->orderId === $order->id && $job->event === 'order_placed');

        $order->update(['status' => 'confirmed']);
        $order->update(['status' => 'shipped']);
        $order->update(['status' => 'delivered']);
        $order->update(['payment_status' => 'partially_refunded']);

        foreach (['order_confirmed', 'order_shipped', 'order_delivered', 'order_refunded'] as $event) {
            Queue::assertPushed(SendOrderNotification::class, fn ($job) => $job->event === $event);
        }

        // A status without a template sends nothing new.
        Queue::fake();
        $order->update(['admin_note' => 'just a note']);
        Queue::assertNotPushed(SendOrderNotification::class);
    }

    public function test_email_is_sent_once_with_tracking_details(): void
    {
      $this->enableEmailTemplates();
        Mail::fake();
        Queue::fake();
        $order = $this->order(['customer_email' => 'buyer@example.com', 'customer_name' => 'Asha', 'awb_code' => 'AWB123', 'courier_name' => 'Delhivery']);

        $this->runJob($order, 'order_shipped');
        $this->runJob($order, 'order_shipped');

        Mail::assertSent(OrderNotificationMail::class, 1);
        Mail::assertSent(OrderNotificationMail::class, function (OrderNotificationMail $mail) use ($order) {
            return $mail->hasTo('buyer@example.com')
                && str_contains($mail->subjectText, $order->order_number)
                && str_contains($mail->bodyHtml, 'AWB123')
                && str_contains($mail->bodyHtml, 'Delhivery');
        });
        $this->assertDatabaseHas('notification_logs', ['order_id' => $order->id, 'event' => 'order_shipped', 'channel' => 'email', 'status' => 'sent']);
    }

    public function test_customer_name_is_escaped_in_the_email(): void
    {
        $this->enableEmailTemplates();
        Mail::fake();
        Queue::fake();
        $order = $this->order(['customer_email' => 'buyer@example.com', 'customer_name' => '<script>alert(1)</script>']);

        $this->runJob($order, 'order_confirmed');

        Mail::assertSent(OrderNotificationMail::class, fn ($mail) => ! str_contains($mail->bodyHtml, '<script>') && str_contains($mail->bodyHtml, '&lt;script&gt;'));
    }

    public function test_disabled_template_sends_nothing(): void
    {
        Mail::fake();
        Queue::fake();
        NotificationTemplate::where('event', 'order_delivered')->where('channel', 'email')->update(['enabled' => false]);
        $order = $this->order(['customer_email' => 'buyer@example.com']);

        $this->runJob($order, 'order_delivered');

        Mail::assertNothingSent();
        $this->assertDatabaseCount('notification_logs', 0);
    }

    public function test_missing_email_is_skipped_not_failed(): void
    {
        $this->enableEmailTemplates();
        Mail::fake();
        Queue::fake();
        $order = $this->order(['customer_email' => null]);

        $this->runJob($order, 'order_confirmed');

        Mail::assertNothingSent();
        $this->assertDatabaseHas('notification_logs', ['order_id' => $order->id, 'channel' => 'email', 'status' => 'skipped']);
    }

    public function test_whatsapp_sends_the_campaign_with_ordered_values(): void
    {
        Queue::fake();
        Http::fake(['*' => Http::response(['success' => true], 200)]);
        config(['services.aisensy.enabled' => true, 'services.aisensy.api_key' => 'test-key', 'services.aisensy.endpoint' => 'https://aisensy.test/api']);
        NotificationTemplate::where('event', 'order_shipped')->where('channel', 'whatsapp')->update([
            'enabled' => true,
            'whatsapp_campaign' => 'ship_camp',
            'whatsapp_params' => ['customer_name', 'order_number', 'awb'],
        ]);
        $order = $this->order(['customer_phone' => '9876543210', 'customer_email' => null, 'customer_name' => 'Asha', 'awb_code' => 'AWB123']);

        $this->runJob($order, 'order_shipped');
        Http::assertSent(fn ($request) => $request['campaignName'] === 'ship_camp'
            && $request['destination'] === '919876543210'
            && $request['templateParams'] === ['Asha', (string) $order->order_number, 'AWB123']);
        $this->assertDatabaseHas('notification_logs', ['order_id' => $order->id, 'channel' => 'whatsapp', 'status' => 'sent']);
    }

    public function test_whatsapp_empty_values_become_a_dash_and_missing_campaign_is_skipped(): void
    {
        Queue::fake();
        Http::fake(['*' => Http::response(['success' => true], 200)]);
        config(['services.aisensy.enabled' => true, 'services.aisensy.api_key' => 'test-key', 'services.aisensy.endpoint' => 'https://aisensy.test/api']);
        $order = $this->order(['customer_phone' => '9876543210', 'customer_email' => null]);

        // No campaign saved yet: skipped.
        NotificationTemplate::where('event', 'order_shipped')->where('channel', 'whatsapp')->update(['enabled' => true]);
        $this->runJob($order, 'order_shipped');
        Http::assertNothingSent();
        $this->assertDatabaseHas('notification_logs', ['order_id' => $order->id, 'channel' => 'whatsapp', 'status' => 'skipped']);

        // Campaign saved, no AWB yet: the empty value is sent as a dash.
        NotificationTemplate::where('event', 'order_shipped')->where('channel', 'whatsapp')->update(['whatsapp_campaign' => 'ship_camp', 'whatsapp_params' => ['order_number', 'awb']]);
        $order->update(['awb_code' => null]);
        NotificationLog::query()->delete();
        $this->runJob($order, 'order_shipped');
        Http::assertSent(fn ($request) => $request['templateParams'] === [(string) $order->order_number, '-']);
    }

    public function test_each_refund_is_notified_once(): void
    {
        $this->enableEmailTemplates();
        Mail::fake();
        Queue::fake();
        $order = $this->order(['customer_email' => 'buyer@example.com', 'payment_status' => 'partially_refunded', 'grand_total' => 1000]);

        $this->adjustment($order, 'partial_refund', 300);
        $this->runJob($order, 'order_refunded');
        $this->runJob($order, 'order_refunded'); // repeat of the same refund
        Mail::assertSent(OrderNotificationMail::class, 1);

        $this->adjustment($order, 'partial_refund', 200);
        $this->runJob($order, 'order_refunded');
        Mail::assertSent(OrderNotificationMail::class, 2);
        Mail::assertSent(OrderNotificationMail::class, fn ($mail) => str_contains($mail->bodyHtml, 'Rs. 200.00'));
    }

    public function test_out_for_delivery_is_notified_once_from_shipment_updates(): void
    {
        Queue::fake();
        $order = $this->order(['awb_code' => 'AWB777', 'shiprocket_shipment_id' => '1', 'shiprocket_status' => 'IN TRANSIT', 'shiprocket_status_at' => now()->subDay()]);
        Queue::fake(); // ignore the jobs from creating the order

        $service = app(ShipmentService::class);
        $service->handleWebhook(['awb' => 'AWB777', 'current_status' => 'OUT FOR DELIVERY', 'current_timestamp' => now()->subHour()->format('d m Y H:i:s')]);
        $service->handleWebhook(['awb' => 'AWB777', 'current_status' => 'OUT FOR DELIVERY', 'current_timestamp' => now()->format('d m Y H:i:s')]);

        Queue::assertPushed(SendOrderNotification::class, 1);
        Queue::assertPushed(SendOrderNotification::class, fn ($job) => $job->event === 'out_for_delivery' && $job->orderId === $order->id);
    }

    public function test_generic_status_email_is_replaced_only_when_a_template_handles_the_status(): void
    {
        $this->enableEmailTemplates();
        $service = app(OrderNotificationService::class);

        $this->assertTrue($service->handlesEmailForStatus('confirmed'));
        $this->assertFalse($service->handlesEmailForStatus('processing'));

        NotificationTemplate::where('event', 'order_confirmed')->where('channel', 'email')->update(['enabled' => false]);
        $this->assertFalse($service->handlesEmailForStatus('confirmed'));
    }

    public function test_admin_page_needs_permission_and_saves_templates(): void
    {
        $this->withoutVite();
        $allowed = $this->adminWith(['manage administration']);
        $denied = $this->adminWith(['manage orders']);
        $email = NotificationTemplate::where('event', 'order_confirmed')->where('channel', 'email')->firstOrFail();
        $whatsapp = NotificationTemplate::where('event', 'order_confirmed')->where('channel', 'whatsapp')->firstOrFail();

        $this->actingAs($denied, 'admin')->get(route('admin.system.notification-templates.index'))->assertForbidden();

        $this->actingAs($allowed, 'admin')->get(route('admin.system.notification-templates.index'))
            ->assertOk()->assertSee('Order confirmed')->assertSee('Recent sends');

        $this->actingAs($allowed, 'admin')->put(route('admin.system.notification-templates.update', $email), [
            'enabled' => '1', 'subject' => 'Hello {order_number}', 'body' => 'Thanks {customer_name}',
        ])->assertSessionHas('success');
        $this->assertSame('Hello {order_number}', $email->fresh()->subject);

        // Email needs a subject and a body.
        $this->actingAs($allowed, 'admin')->put(route('admin.system.notification-templates.update', $email), ['enabled' => '1', 'subject' => '', 'body' => ''])
            ->assertSessionHasErrors(['subject', 'body']);

        // WhatsApp cannot be switched on without a campaign, and only known values are accepted.
        $this->actingAs($allowed, 'admin')->put(route('admin.system.notification-templates.update', $whatsapp), ['enabled' => '1', 'whatsapp_campaign' => '', 'whatsapp_params' => 'order_number'])
            ->assertSessionHasErrors(['whatsapp_campaign']);
        $this->actingAs($allowed, 'admin')->put(route('admin.system.notification-templates.update', $whatsapp), ['enabled' => '0', 'whatsapp_campaign' => 'x', 'whatsapp_params' => 'order_number, bogus'])
            ->assertSessionHasErrors(['whatsapp_params']);
        $this->actingAs($allowed, 'admin')->put(route('admin.system.notification-templates.update', $whatsapp), ['enabled' => '1', 'whatsapp_campaign' => 'confirm_camp', 'whatsapp_params' => 'customer_name, order_number'])
            ->assertSessionHas('success');
        $whatsapp->refresh();
        $this->assertTrue($whatsapp->enabled);
        $this->assertSame(['customer_name', 'order_number'], $whatsapp->whatsapp_params);
    }

    private function runJob(Order $order, string $event): void
    {
        (new SendOrderNotification($order->id, $event))->handle(app(OrderNotificationService::class));
    }

    private function adjustment(Order $order, string $type, float $amount): void
    {
        OrderPaymentAdjustment::create([
            'order_id' => $order->id, 'type' => $type, 'amount' => $amount,
            'from_payment_status' => 'paid', 'to_payment_status' => 'partially_refunded',
            'reason' => 'Test refund reason', 'reference' => 'REF-'.uniqid(),
            'adjusted_by_name' => 'Tester', 'created_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function order(array $attributes = []): Order
    {
        return Order::factory()->create(array_merge([
            'status' => 'confirmed',
            'payment_method' => 'razorpay',
            'grand_total' => 1000,
        ], $attributes));
    }

    /** @param list<string> $permissions */
    private function adminWith(array $permissions): User
    {
        foreach (['manage orders', 'manage administration'] as $permission) {
            Permission::findOrCreate($permission, 'admin');
        }

        $role = Role::create(['name' => 'Role '.uniqid(), 'guard_name' => 'admin']);
        $role->givePermissionTo($permissions);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        return $admin;
    }
        private function enableEmailTemplates(): void
    {
        \App\Models\NotificationTemplate::query()->where('channel', 'email')->update(['enabled' => true]);
    }
}
