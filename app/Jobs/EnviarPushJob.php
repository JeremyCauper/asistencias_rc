<?php

namespace App\Jobs;

use App\Http\Controllers\PushController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnviarPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;
    public array $payload;

    public function __construct(int $userId, array $payload)
    {
        $this->userId = $userId;
        $this->payload = $payload;
    }

    public function handle(): void
    {
        PushController::send($this->userId, $this->payload);
    }
}