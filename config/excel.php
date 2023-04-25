<?php

// Ref: https://github.com/SpartnerNL/Laravel-Excel/blob/3.1/config/excel.php

return [
    'temporary_files' => [
        'local_path'          => storage_path('framework/cache/laravel-excel'),
        'remote_disk'         => null,
        'remote_prefix'       => null,
        'force_resync_remote' => null,
    ],
];
