<?php

namespace App\Console\Commands;

use App\Models\AdditionalUser;
use App\Models\User;
use Illuminate\Console\Command;

class StatusUserUpdate extends Command
{
    protected $signature = 'command:user-status';

    protected $description = 'Alter diary status user';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $users = User::whereNotNull('return_date')
            ->where('return_date', '<', date('Y-m-d H:i:s'))
            ->where('status', '!=', 2)
            ->get();

        foreach ($users as $user) {
            activity()->disableLogging();
            AdditionalUser::where('user_additional_id', $user->id)->delete();
            User::where('id', $user->id)
                ->update([
                    'return_date' => null,
                    'status' => 0
                ]);
            activity()->enableLogging();
        }
    }
}
