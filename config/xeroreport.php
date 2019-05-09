<?php

return [
    'xero' => [
        'oauth' => [

            'callback'        => 'oob',
            'consumer_key'    => env('XERO_CONSUMER_KEY', null),
            'consumer_secret' => env('XERO_CONSUMER_SECRET', null),

            // For certs on disk or a string
            // allows anything that is valid with openssl_pkey_get_(private|public)
            // ref: https://developer.xero.com/documentation/api-guides/create-publicprivate-key
            'rsa_private_key' => 'file://storage/app/certs/privatekey.pem',
            'rsa_public_key'  => 'file://storage/app/certs/publickey.cer',

        ],
    ],
    'notifications' => [
        'slack' => [

            // ref: https://api.slack.com/incoming-webhooks
            'webhook_url' => env('XERO_SLACK_WEBHOOK_URL', null),

            // default channel of webhook will be used, if set to null
            'channel'     => [
                'default'        => env('XERO_SLACK_DEFAULT_CHANNEL', null),
                'full_report'    => env('XERO_FULL_REPORT_SLACK_CHANNEL', null),
                'partial_report' => env('XERO_PARTIAL_REPORT_SLACK_CHANNEL', null),
            ],

            'username'    => env('XERO_SLACK_USERNAME', null),

            'icon'        => env('XERO_SLACK_ICON', null),

        ],
    ],
];
