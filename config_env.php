<?php
return [
    'db' => [
        'db_host' => getenv('SQL_HOST'),
        'db_name' => getenv('SQL_DATABASE'),
        'db_user' => getenv('SQL_USER'),
        'db_password' => getenv('SQL_PASSWORD'),
    ],

    'decoders' => [
        'env' => [
            'address' => getenv('DECODER_ADDRESS'),
            'id' => getenv('DECODER_ID'),
            'mylaps_id' => getenv('DECODER_MYLAPS_ID'),
        ],
    ]
];
