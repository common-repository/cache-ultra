<?php 
return [
    [
        'methods' => 'POST',
        'path' => '/snapshot',
        'callback' => [
            '\CacheUltra\Controllers\SnapshotController',
            'snapshot'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'POST',
        'path' => '/snapshot/poll',
        'callback' => [
            '\CacheUltra\Controllers\SnapshotController',
            'getArchiveSnapshot'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'GET',
        'path' => '/snapshot/cancel/(?P<id>\d+)',
        'callback' => [
            '\CacheUltra\Controllers\SnapshotController',
            'cancel'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'GET',
        'path' => '/snapshot/clear/(?P<id>\d+)',
        'callback' => [
            '\CacheUltra\Controllers\SnapshotController',
            'clear'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'POST',
        'path' => '/snapshot/clear',
        'callback' => [
            '\CacheUltra\Controllers\SnapshotController',
            'clearArchive'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'GET',
        'path' => '/snapshot/(?P<id>\d+)',
        'callback' => [
            '\CacheUltra\Controllers\SnapshotController',
            'getSnapshot'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'POST',
        'path' => '/snapshot/save',
        'callback' => [
            '\CacheUltra\Controllers\SnapshotController',
            'save'
        ],
        'permission' => [
            'method' => 'ValidateToken',
        ],
    ],
    [
        'methods' => 'POST',
        'path' => '/options/update',
        'callback' => [
            '\CacheUltra\Controllers\SnapshotController',
            'updateOptions'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'POST',
        'path' => '/resourcemap',
        'callback' => [
            '\CacheUltra\Controllers\ResourceController',
            'process'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'POST',
        'path' => '/resourcemap/save',
        'callback' => [
            '\CacheUltra\Controllers\ResourceController',
            'save'
        ],
        'permission' => false,
    ],
    [
        'methods' => 'POST',
        'path' => '/resourcemap/check',
        'callback' => [
            '\CacheUltra\Controllers\ResourceController',
            'check'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'POST',
        'path' => '/resourcemap/all',
        'callback' => [
            '\CacheUltra\Controllers\ResourceController',
            'all'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'POST',
        'path' => '/resourcemap/cancel',
        'callback' => [
            '\CacheUltra\Controllers\ResourceController',
            'cancel'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'POST',
        'path' => '/resourcemap/flush',
        'callback' => [
            '\CacheUltra\Controllers\ResourceController',
            'flushResources'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'POST',
        'path' => '/resourcemap/resource/save',
        'callback' => [
            '\CacheUltra\Controllers\ResourceController',
            'saveResource'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'POST',
        'path' => '/resourcemap/resource/test',
        'callback' => [
            '\CacheUltra\Controllers\ResourceController',
            'test'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'POST',
        'path' => '/resourcemap/resources/save',
        'callback' => [
            '\CacheUltra\Controllers\ResourceController',
            'saveResources'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'POST',
        'path' => '/cache/create',
        'callback' => [
            '\CacheUltra\Controllers\CacheController',
            'create'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'POST',
        'path' => '/cache/update',
        'callback' => [
            '\CacheUltra\Controllers\CacheController',
            'update'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'POST',
        'path' => '/cache/resources',
        'callback' => [
            '\CacheUltra\Controllers\CacheController',
            'resources'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'POST',
        'path' => '/cache/save',
        'callback' => [
            '\CacheUltra\Controllers\CacheController',
            'save'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'POST',
        'path' => '/cache/remove',
        'callback' => [
            '\CacheUltra\Controllers\CacheController',
            'remove'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'GET',
        'path' => '/cache/load',
        'callback' => [
            '\CacheUltra\Controllers\CacheController',
            'load'
        ],
        'permission' => false,
    ],
    [
        'methods' => 'GET',
        'path' => '/cache/pages',
        'callback' => [
            '\CacheUltra\Controllers\CacheController',
            'pages'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'GET',
        'path' => '/cache/performance/check/(?P<id>\d+)',
        'callback' => [
            '\CacheUltra\Controllers\CacheController',
            'checkPerformance'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'GET',
        'path' => '/cache/snapshot/check/(?P<id>\d+)',
        'callback' => [
            '\CacheUltra\Controllers\SnapshotController',
            'checkSnapshot'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'POST',
        'path' => '/cache/performance/save',
        'callback' => [
            '\CacheUltra\Controllers\CacheController',
            'savePerformance'
        ],
        'permission' => [
            'method' => 'ValidateToken',
        ],
    ],
    [
        'methods' => 'GET',
        'path' => '/cache/performance/cancel/(?P<id>\d+)',
        'callback' => [
            '\CacheUltra\Controllers\CacheController',
            'cancelPerformance'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'GET',
        'path' => '/cache/snapshot/cancel/(?P<id>\d+)',
        'callback' => [
            '\CacheUltra\Controllers\CacheController',
            'cancelSnapshot'
        ],
        'permission' => 'manage_options',
    ],
    [
        'methods' => 'POST',
        'path' => '/file/unlink',
        'callback' => [
            '\CacheUltra\Controllers\FileController',
            'unlinkFile'
        ],
        'permission' => [
            'method' => 'ValidateToken',
        ],
    ],
];