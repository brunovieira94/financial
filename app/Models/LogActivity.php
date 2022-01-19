<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity;

class LogActivity extends Activity
{
    protected $casts = [
        'properties' => 'collection',
        'causer_object' => 'collection',
    ];
}
