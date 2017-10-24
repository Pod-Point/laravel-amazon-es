<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Configuration
    |--------------------------------------------------------------------------
    */

    'hosts' => [
        env('ELASTICSEARCH_HOST', 'localhost'),
    ],

    'aws' => [
        'key'    => env('AWS_KEY'),
        'secret' => env('AWS_SECRET'),
        'region' => env('AWS_REGION', 'eu-west-1'),
    ],
];
