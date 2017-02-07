<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
        'env' => 'live',
        'system_email' => 'no-reply@smartlouisville.com',
        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'api.smartlouisville.com',
            'path' => __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        'twilio' => [
            'test' => [
                'keysid' => '',
                'keysecret' => '',
                'accountsid' => '',
                'authtoken' => '',
                'number' => ''
            ],
            'live' => [
                'keysid' => '',
                'keysecret' => '',
                'accountsid' => '',
                'authtoken' => '',
                'number' => ''
            ]
        ],

        'db' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => '',
            'username' => '',
            'password' => '',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ],

        'sendgrid' => [
            'api_key' => ''
        ],
    ],
];
