<?php

use App\Console\Commands\ExpireOverridesCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command(ExpireOverridesCommand::class)->everyMinute();
