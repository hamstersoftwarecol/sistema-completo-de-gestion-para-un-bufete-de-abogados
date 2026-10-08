<?php

namespace App\Console\Commands;

use App\Services\Notifier;
use Illuminate\Console\Command;

class SendHearingReminders extends Command
{
    protected $signature = 'bufete:recordatorios';

    protected $description = 'Notifica a los abogados las audiencias de las próximas 24 horas';

    public function handle(): int
    {
        $n = Notifier::sendHearingReminders();
        $this->info("Recordatorios enviados para {$n} audiencia(s).");

        return self::SUCCESS;
    }
}
