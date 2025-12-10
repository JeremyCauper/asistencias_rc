<?php

namespace App\Jobs;

use App\Http\Controllers\PushController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnviarPushAdminsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $ids;
    public array $payload;

    public function __construct(array $ids, array $payload)
    {
        $this->ids = $ids;
        $this->payload = $payload;
    }

    public function handle(): void
    {
        foreach ($this->ids as $id) {
            PushController::send($id, $this->payload);
        }
    }
}