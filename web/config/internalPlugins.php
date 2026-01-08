<?php

return [
    'rtf' => [
        'endpoint' => env('RTFENDPOINT', null),
        'pwd' => env('RTFPASSWORD', null),
        'formats' => env('RTFFORMATS', null),
    ],
    'aTrainUpload' => env('SERVICE_TRANSFORM_ATRAIN_UPLOAD', null),
    'aTrainProcess' => env('SERVICE_TRANSFORM_ATRAIN_PROCESS', null),
    'aTrainDownload' => env('SERVICE_TRANSFORM_ATRAIN_DOWNLOAD', null),
    'aTrainDelete' => env('SERVICE_TRANSFORM_ATRAIN_DELETE', null),
];
