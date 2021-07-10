<?php
return [
    'db' => [
        'db_host' => '127.0.0.1',
        'db_name' => getenv('DB_DATABASE'),
        'db_user' => getenv('DB_USER'),
        'db_password' => getenv('DB_PASSWORD'),
    ],
    'decoders' => [
        'test_decoder' => [
            'address' => '127.0.0.1',
            'id' => '20f93',
            'mylaps_id' => 238
        ]
    ]
];