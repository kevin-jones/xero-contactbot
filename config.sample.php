<?php
$config = [
    'production' => [
        'keys' => [
            'public' => '1234567890',
            'private' => '1234567890',
            'rsa_private_key' => "/path/to/private.key",
            'rsa_public_key' => "/path/to/public.cer",
        ],
    ]
];

define('ENVIRONMENT', 'production');


