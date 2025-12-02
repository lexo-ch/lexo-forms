<?php

if (!defined('ABSPATH')) {
    exit; // Don't access directly
};

use const LEXO\LF\{
    PATH,
    URL
};

return [
    'priority'  => 90,
    'dist_path' => PATH . 'dist',
    'dist_uri'  => URL . 'dist',
    'assets'    => [
        'front' => [
            'styles'    => ['css/lexoforms-frontend.css'],
            'scripts'   => ['js/frontend.js']
        ],
        'admin' => [
            'styles'    => ['css/admin-lf.css'],
            'scripts'   => [
                'js/admin-lf.js'
            ]
        ],
        'editor' => [
            'styles'    => []
        ],
    ]
];
