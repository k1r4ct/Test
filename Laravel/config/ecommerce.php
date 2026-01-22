<?php

/**
 * E-commerce Configuration
 * 
 * This file contains all configurable settings for the Semprechiaro e-commerce module.
 * 
 * Usage:
 *   config('ecommerce.cart.expiration_minutes')
 *   config('ecommerce.pv.conversion_rate')
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Cart Settings
    |--------------------------------------------------------------------------
    |
    | Configure cart behavior, expiration, and limits.
    |
    */
    'cart' => [
        // Cart expiration time in minutes (default: 30)
        // After this time, cart items are automatically removed and PV released
        'expiration_minutes' => env('CART_EXPIRATION_MINUTES', 30),

        // Maximum items per cart
        'max_items' => env('CART_MAX_ITEMS', 10),

        // Maximum quantity per item
        'max_quantity_per_item' => env('CART_MAX_QTY_PER_ITEM', 10),

        // Cleanup job frequency in minutes (default: 10)
        'cleanup_frequency_minutes' => env('CART_CLEANUP_FREQUENCY', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | PV (Punti Valore) Settings
    |--------------------------------------------------------------------------
    |
    | Configure how PV are handled in the e-commerce system.
    |
    */
    'pv' => [
        // PV to Euro conversion rate (default: 20 PV = 1€)
        'conversion_rate' => env('PV_CONVERSION_RATE', 20),

        // Minimum PV required to place an order
        'minimum_order_pv' => env('PV_MINIMUM_ORDER', 0),

        // Allow partial payment with PV + real money (future feature)
        'allow_partial_payment' => env('PV_ALLOW_PARTIAL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Settings
    |--------------------------------------------------------------------------
    |
    | Configure order processing and fulfillment.
    |
    */
    'order' => [
        // Order number prefix
        'number_prefix' => env('ORDER_NUMBER_PREFIX', 'SC'),

        // Order number format: {prefix}-{year}{month}-{sequence}
        // Example: SC-202601-00001
        'number_format' => '{prefix}-{ym}-{seq}',

        // Default priority for new orders
        'default_priority' => 'normal', // low, normal, high, urgent

        // Auto-assign orders to backoffice (null = no auto-assign)
        'auto_assign_to_user_id' => env('ORDER_AUTO_ASSIGN_USER', null),

        // Send notification email on new order
        'notify_on_new_order' => env('ORDER_NOTIFY_NEW', true),

        // Send notification email on order completion
        'notify_on_completion' => env('ORDER_NOTIFY_COMPLETE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure who receives notifications for e-commerce events.
    |
    */
    'notifications' => [
        // Role IDs that should receive backoffice notifications
        'backoffice_roles' => [1, 5], // Administrator, BackOffice

        // Email addresses for admin notifications (comma-separated in env)
        'admin_emails' => env('ECOMMERCE_ADMIN_EMAILS', ''),

        // Notification channels: 'database', 'mail', 'both'
        'channels' => env('ECOMMERCE_NOTIFICATION_CHANNEL', 'both'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Digital Products Settings
    |--------------------------------------------------------------------------
    |
    | Settings specific to digital products like gift cards.
    |
    */
    'digital' => [
        // Automatically mark digital products as fulfilled when code is entered
        'auto_fulfill_on_code' => true,

        // Mask redemption codes in logs (show only last 4 characters)
        'mask_codes_in_logs' => true,

        // Send redemption code via email
        'send_code_via_email' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Stock Settings
    |--------------------------------------------------------------------------
    |
    | Configure stock management behavior.
    |
    */
    'stock' => [
        // Enable stock tracking (false for unlimited digital products)
        'track_stock' => env('TRACK_STOCK', true),

        // Low stock alert threshold multiplier
        // Alert when: quantity <= minimum_stock
        'low_stock_alert' => true,

        // Allow overselling (checkout when out of stock)
        'allow_oversell' => env('ALLOW_OVERSELL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Future Payment Methods (Coming Soon)
    |--------------------------------------------------------------------------
    |
    | Settings for PayPal, Stripe, and other payment methods.
    |
    */
    'payments' => [
        'paypal' => [
            'enabled' => env('PAYPAL_ENABLED', false),
            'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
            'client_id' => env('PAYPAL_CLIENT_ID', ''),
            'client_secret' => env('PAYPAL_CLIENT_SECRET', ''),
        ],

        'stripe' => [
            'enabled' => env('STRIPE_ENABLED', false),
            'public_key' => env('STRIPE_PUBLIC_KEY', ''),
            'secret_key' => env('STRIPE_SECRET_KEY', ''),
        ],
    ],

];
