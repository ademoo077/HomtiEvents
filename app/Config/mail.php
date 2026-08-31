<?php

declare(strict_types=1);

return [
    'host'       => env('MAIL_HOST', 'smtp.gmail.com'),
    'port'       => (int) env('MAIL_PORT', 587),
    'username'   => env('MAIL_USERNAME', ''),
    'password'   => env('MAIL_PASSWORD', ''),
    'from'       => env('MAIL_FROM_ADDRESS', ''),
    'from_name'  => env('MAIL_FROM_NAME', 'حومتي ايفانت'),
    'encryption' => env('MAIL_ENCRYPTION', 'tls'),
];
