<?php

// Ref: https://github.com/SpartnerNL/Laravel-Excel/blob/3.1/config/excel.php

return [
    'temporary_files' => [
        'local_path'          => 'export',
        'remote_disk'         => 's3',
        'remote_prefix'       => null,
        'force_resync_remote' => null,
    ],
];
