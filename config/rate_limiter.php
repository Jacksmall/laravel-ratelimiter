<?php
return [
    'prefix' => 'rate_limit',
    // 默认限流配置
    'defaults' => [
        'capacity' => 100,
        'rate' => 10, // 每秒令牌数
        'cost' => 1, // 每次请求消耗令牌
        'cool_down' => 30, // 冷却时间（秒）
    ],
    // 服务限流配置
    'services' => [
        'service1' => [
            'capacity' => 50,
            'rate' => 5,
            'cost' => 2
        ],
        'service2' => [
            'capacity' => 200,
            'rate' => 20
        ],
    ],
    'http_client' => [
        'global' => [
            'capacity' => 100,
            'rate' => 20,
        ],
        'services' => [
            'external_api' => [
                'capacity' => 50,
                'rate' => 10,
            ]
        ]
    ]
];
