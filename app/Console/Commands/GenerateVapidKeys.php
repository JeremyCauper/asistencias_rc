<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    protected $signature = 'webpush:keys';
    protected $description = 'Generar llaves VAPID';

    public function handle()
    {
        $keys = VAPID::createVapidKeys();

        $this->info("Public Key:");
        $this->line($keys['publicKey']);

        $this->info("Private Key:");
        $this->line($keys['privateKey']);
    }
}
