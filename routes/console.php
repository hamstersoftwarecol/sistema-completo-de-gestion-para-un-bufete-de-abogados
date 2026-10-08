<?php

use Illuminate\Support\Facades\Schedule;

// Recordatorios de audiencias (requiere "php artisan schedule:work" o una tarea programada).
Schedule::command('bufete:recordatorios')->hourly();
