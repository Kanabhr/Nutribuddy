@php
    $user = auth()->user();
    $ordersQuery = $user ? \App\Models\Order::where('user_id', $user->id) : \App\Models\Order::query()->whereRaw('1 = 0');
    $orderCount = (clone $ordersQuery)->count();
    $pendingOrderCount = (clone $ordersQuery)->whereIn('status', ['pending', 'confirmed', 'processing', 'packed'])->count();
    $spentTotal = (clone $ordersQuery)->where('payment_status', 'paid')->sum('grand_total');
    $reviewCount = $user ? \App\Models\ProductReview::where('user_id', $user->id)->count() : 0;
    $usedCouponCount = $user ? \App\Models\CouponUsage::where('user_id', $user->id)->count() : 0;
    $ticketQuery = $user ? \App\Models\SupportTicket::where('user_id', $user->id) : \App\Models\SupportTicket::query()->whereRaw('1 = 0');
    $ticketCount = (clone $ticketQuery)->count();
    $openTicketCount = (clone $ticketQuery)->whereIn('status', ['open', 'pending'])->count();
    $returnQuery = \App\Models\OrderReturn::whereHas('order', fn ($query) => $query->where('user_id', $user?->id));
    $returnCount = (clone $returnQuery)->count();
    $pendingReturnCount = (clone $returnQuery)->whereIn('status', ['pending', 'approved'])->count();
    $addressCount = $user ? \App\Models\CustomerAddress::where('user_id', $user->id)->count() : 0;

    $hero = [
        'title' => 'Account',
        'subtitle' => 'Manage your NutriBuddy account from here.',
        'stats' => [
            ['value' => $orderCount, 'label' => 'Orders'],
            ['value' => $reviewCount, 'label' => 'Reviews'],
        ],
    ];

    if (request()->routeIs('wallet')) {
        $hero = [
            'title' => 'NB Coins Wallet',
            'subtitle' => 'Track your coin balance, rewards, and redemption history.',
            'stats' => [
                ['value' => number_format((int) ($user?->coins_balance ?? 0)), 'label' => 'Coins'],
                ['value' => 'Rs. ' . number_format(((int) ($user?->coins_balance ?? 0)) / 10, 0), 'label' => 'Value'],
            ],
        ];
    } elseif (request()->routeIs('meal-plan')) {
        $hero = [
            'title' => '7-Day Meal Plan',
            'subtitle' => 'A personalized weekly food rhythm for your child.',
            'stats' => [
                ['value' => '7', 'label' => 'Days'],
                ['value' => 'Active', 'label' => 'Plan'],
            ],
        ];
    } elseif (request()->routeIs('growth-signal')) {
        $hero = [
            'title' => 'Health Scores',
            'subtitle' => 'Review growth, nutrition, immunity, and focus progress.',
            'stats' => [
                ['value' => '75', 'label' => 'Score'],
                ['value' => '+21', 'label' => 'Points'],
            ],
        ];
    } elseif (request()->routeIs('supplement')) {
        $hero = [
            'title' => 'Supplement Schedule',
            'subtitle' => 'Daily gummies and timing guidance in one place.',
            'stats' => [
                ['value' => '3', 'label' => 'Active'],
                ['value' => 'Daily', 'label' => 'Routine'],
            ],
        ];
    } elseif (request()->routeIs('child-profile')) {
        $child = auth()->user()->children()->latest()->first();
        $assessment = $child ? $child->assessments()->latest()->first() : null;
        
        $hero = [
            'title' => 'Child Profile',
            'subtitle' => 'Keep your child details, preferences, and care context updated.',
            'stats' => [
                ['value' => $child ? \Carbon\Carbon::parse($child->dob)->age : '0', 'label' => 'Years'],
                ['value' => $assessment ? ucfirst($assessment->diet_type) : 'Veg', 'label' => 'Diet'],
            ],
        ];
    } elseif (request()->routeIs('growth-signal')) {
        $hero = [
            'title' => 'Growth Signals',
            'subtitle' => 'Watch adaptation signals and progress patterns over time.',
            'stats' => [
                ['value' => 'Live', 'label' => 'Signals'],
                ['value' => 'Quarterly', 'label' => 'Review'],
            ],
        ];
    } elseif (request()->routeIs('check-in')) {
        $hero = [
            'title' => 'Quarterly Check-in',
            'subtitle' => 'Capture progress updates and nutrition changes each quarter.',
            'stats' => [
                ['value' => '3 mo', 'label' => 'Cycle'],
                ['value' => 'Open', 'label' => 'Check-in'],
            ],
        ];
    } elseif (request()->routeIs('order')) {
        $hero = [
            'title' => 'My Orders',
            'subtitle' => 'Track and manage all NutriBuddy purchases.',
            'stats' => [
                ['value' => number_format($orderCount), 'label' => 'Orders'],
                ['value' => number_format($pendingOrderCount), 'label' => 'Pending'],
            ],
        ];
    } elseif (request()->routeIs('user.orders.detail-page')) {
        $order = request()->route('order');
        $hero = [
            'title' => $order?->order_number ? 'Order #' . $order->order_number : 'Order Details',
            'subtitle' => 'View items, status timeline, payment, and delivery details.',
            'stats' => [
                ['value' => strtoupper($order?->status ?? 'Order'), 'label' => 'Status'],
                ['value' => 'Rs. ' . number_format((float) ($order?->grand_total ?? 0), 0), 'label' => 'Total'],
            ],
        ];
    } elseif (request()->routeIs('user.orders.invoice-page')) {
        $order = request()->route('order');
        $hero = [
            'title' => $order?->order_number ? 'Invoice #' . $order->order_number : 'Invoice Details',
            'subtitle' => 'Review billing, GST, payment, and downloadable PDF invoice.',
            'stats' => [
                ['value' => strtoupper($order?->payment_status ?? 'Invoice'), 'label' => 'Payment'],
                ['value' => 'PDF', 'label' => 'Download'],
            ],
        ];
    } elseif (request()->routeIs('user.invoices.*')) {
        $paidInvoices = (clone $ordersQuery)->where('payment_status', 'paid')->count();
        $hero = [
            'title' => 'Invoices',
            'subtitle' => 'Download and review invoices for your paid orders.',
            'stats' => [
                ['value' => number_format($paidInvoices), 'label' => 'Paid'],
                ['value' => 'Rs. ' . number_format((float) $spentTotal, 0), 'label' => 'Spent'],
            ],
        ];
    } elseif (request()->routeIs('user.orders.returns.*')) {
        $hero = [
            'title' => 'Returns',
            'subtitle' => 'Track return requests and refund progress.',
            'stats' => [
                ['value' => number_format($returnCount), 'label' => 'Returns'],
                ['value' => number_format($pendingReturnCount), 'label' => 'Pending'],
            ],
        ];
    } elseif (request()->routeIs('user.reviews.index')) {
        $hero = [
            'title' => 'My Reviews',
            'subtitle' => 'Manage product ratings and feedback you have shared.',
            'stats' => [
                ['value' => number_format($reviewCount), 'label' => 'Reviews'],
                ['value' => '5', 'label' => 'Stars'],
            ],
        ];
    } elseif (request()->routeIs('user.support-tickets')) {
        $hero = [
            'title' => 'Support Tickets',
            'subtitle' => 'Ask questions and track help requests from the NutriBuddy team.',
            'stats' => [
                ['value' => number_format($ticketCount), 'label' => 'Tickets'],
                ['value' => number_format($openTicketCount), 'label' => 'Open'],
            ],
        ];
    } elseif (request()->routeIs('user.support-tickets.show')) {
        $ticket = request()->route('ticket');
        $hero = [
            'title' => $ticket?->ticket_number ? 'Ticket #' . $ticket->ticket_number : 'Ticket Details',
            'subtitle' => $ticket?->subject ?: 'Continue your conversation with support.',
            'stats' => [
                ['value' => strtoupper($ticket?->status ?? 'Open'), 'label' => 'Status'],
                ['value' => strtoupper($ticket?->priority ?? 'Normal'), 'label' => 'Priority'],
            ],
        ];
    } elseif (request()->routeIs('personal-info') || request()->routeIs('user.addresses.*')) {
        $hero = [
            'title' => request()->routeIs('user.addresses.create') ? 'Add Address' : (request()->routeIs('user.addresses.edit') ? 'Edit Address' : 'Personal Info'),
            'subtitle' => 'Manage profile information and delivery addresses.',
            'stats' => [
                ['value' => number_format($addressCount), 'label' => 'Addresses'],
                ['value' => 'Secure', 'label' => 'Profile'],
            ],
        ];
    } elseif (request()->routeIs('subscription')) {
        $hero = [
            'title' => 'Subscription',
            'subtitle' => 'Review your NutriBuddy care plan and benefits.',
            'stats' => [
                ['value' => 'Plan', 'label' => 'Status'],
                ['value' => '15%', 'label' => 'Benefit'],
            ],
        ];
    } elseif (request()->routeIs('my-coupons')) {
        $hero = [
            'title' => 'My Coupons',
            'subtitle' => 'See coupons already used on your NutriBuddy orders.',
            'stats' => [
                ['value' => number_format($usedCouponCount), 'label' => 'Used'],
                ['value' => 'History', 'label' => 'View'],
            ],
        ];
    } elseif (request()->routeIs('user-return')) {
        $hero = [
            'title'    => 'Return Policy',
            'subtitle' => 'Understand return eligibility, timelines, and refund rules.',
            'stats'    => [
                ['value' => 'Sealed', 'label' => 'Items'],
                ['value' => 'Policy', 'label' => 'Guide'],
            ],
        ];
    } elseif (request()->routeIs('user.assessment.index')) {
        $questions = \App\Models\AssessmentQuestion::active()->count();
        $hero = [
            'title'    => 'Health Assessment',
            'subtitle' => 'Answer every question honestly to get the most accurate score for your child.',
            'stats'    => [
                ['value' => $questions, 'label' => 'Questions'],
                ['value' => '5',       'label' => 'Sections'],
            ],
        ];
    } elseif (request()->routeIs('user.assessment.history')) {
        $attemptCount = $user ? \App\Models\AssessmentAttempt::where('user_id', $user->id)->count() : 0;
        $hero = [
            'title'    => 'Assessment History',
            'subtitle' => 'All previous child health assessments, newest first.',
            'stats'    => [
                ['value' => $attemptCount, 'label' => 'Attempts'],
                ['value' => 'Health',      'label' => 'Track'],
            ],
        ];
    } elseif (request()->routeIs('user.assessment.result')) {
        $attempt = request()->route('attempt');
        $hero = [
            'title'    => 'Assessment Result',
            'subtitle' => 'Your child health score breakdown and section insights.',
            'stats'    => [
                ['value' => $attempt ? number_format($attempt->percentage, 0) . '%' : '-', 'label' => 'Score'],
                ['value' => $attempt?->result_level ?? '-', 'label' => 'Rating'],
            ],
        ];
    } elseif (request()->routeIs('user.assessment.compare')) {
        $hero = [
            'title'    => 'Assessment Comparison',
            'subtitle' => 'Compare your child\'s progress between the last two attempts.',
            'stats'    => [
                ['value' => '2',        'label' => 'Attempts'],
                ['value' => 'Progress', 'label' => 'Insight'],
            ],
        ];
    } elseif (request()->routeIs('user.assessment.detail')) {
        $attempt = request()->route('attempt');
        $hero = [
            'title'    => 'Assessment Breakdown',
            'subtitle' => 'Question-by-question answer and score review.',
            'stats'    => [
                ['value' => $attempt ? $attempt->total_score . '/' . $attempt->max_score : '-', 'label' => 'Score'],
                ['value' => $attempt?->result_level ?? '-', 'label' => 'Rating'],
            ],
        ];
    }
@endphp

@unless(request()->routeIs('userdashboard') || request()->routeIs('subscription') || request()->routeIs('personal-info') || request()->routeIs('user.assessment.*'))
    <section class="user-panel-page-hero">
        <div class="user-panel-page-hero__text">
            <h1>{{ $hero['title'] }}</h1>
            <p>{{ $hero['subtitle'] }}</p>
        </div>
        <div class="user-panel-page-hero__stats">
            @foreach($hero['stats'] as $stat)
                <div class="user-panel-page-hero__stat">
                    <strong>{{ $stat['value'] }}</strong>
                    <span>{{ $stat['label'] }}</span>
                </div>
            @endforeach
        </div>
    </section>
@endunless
