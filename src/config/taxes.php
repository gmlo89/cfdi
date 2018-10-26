<?php

return [
    'isr' => [
        'code' => '001',
        'description' => 'ISR',
        'retention' => true,
        'transfer' => false,
        'federal' => true,
        'rates' => [
            'retention' => 'min:0|max:.35'
        ]
    ],
    'iva' => [
        'code' => '002',
        'description' => 'IVA',
        'retention' => true,
        'transfer' => true,
        'federal' => true,
        'rates' => [
            'transfer' => 'in:0,.16',
            'retention' => 'min:0|max:.16'
        ]
    ],
    'ieps' => [
        'code' => '003',
        'description' => 'IEPS',
        'retention' => true,
        'transfer' => true,
        'federal' => true,
        'rates' => [
            'transfer' => 'in:0.265,0.300,0.530,0.500,1.600,0.304,0.250,0.090,0.080,0.070,0.060,0.030,0.000',
            'retention' => 'in:0.265,0.300,0.530,0.500,1.600,0.304,0.250,0.090,0.080,0.070,0.060',
        ],
        'fee' => [
            'transfer' => 'min:0|max:43.77',
            'retention' => 'min:0|max:43.77'
        ]
    ],
];
