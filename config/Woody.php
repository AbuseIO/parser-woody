<?php

return [
    'parser' => [
        'name'          => 'Woody',
        'enabled'       => true,
        'sender_map'    => [
            '/nobody@woody.ch/',
        ],
        'body_map'      => [
            //
        ],
    ],

    'feeds' => [
        'default' => [
            'class'     => 'SPAM',
            'type'      => 'ABUSE',
            'enabled'   => true,
            'fields'    => [
                'Source-IP',
                'Feedback-Type',
                'Received-Date',
            ],
        ],

    ],
];
