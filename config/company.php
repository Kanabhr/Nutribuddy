<?php
return [
    'name' => env('COMPANY_NAME', 'NutriBuddy Kids Pvt. Ltd.'),
    'gst' => env('COMPANY_GST', '27AAAAA0000A1Z5'),
    'pan' => env('COMPANY_PAN', 'AAAAA0000A'),
    'cin' => env('COMPANY_CIN', 'U74999MH2020PTC123456'),
    'address' => env('COMPANY_ADDRESS', '123, Business Park'),
    'city' => env('COMPANY_CITY', 'Mumbai, Maharashtra - 400069'),
    'phone' => env('COMPANY_PHONE', '+91 98765 43210'),
    'email' => env('COMPANY_EMAIL', 'support@nutribuddy.com'),
    'website' => env('COMPANY_WEBSITE', 'www.nutribuddy.com'),

    // Coin System Settings
    'coin_to_cash_rate' => 10, // 10 coins = ₹1
    'max_redeem_percent' => 30, // Max 30% of order value can be paid via coins
];