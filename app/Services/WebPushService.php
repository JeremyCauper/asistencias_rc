<?php

namespace App\Services;

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use App\Models\PushSubscription;
use App\Models\User;

class WebPushService
{
    public function sendNotification($userId, $title, $body)
    {
        $user = User::where('user_id', $userId)->first();

        if (!$user)
            return false;

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => env('VAPID_SUBJECT'),
                'publicKey' => env('VAPID_PUBLIC_KEY'),
                'privateKey' => env('VAPID_PRIVATE_KEY'),
            ]
        ]);

        foreach ($user->pushSubscriptions as $sub) {

            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'keys' => [
                    'p256dh' => $sub->p256dh,
                    'auth' => $sub->auth,
                ],
            ]);

            $payload = json_encode([
                'title' => $title,
                'body' => $body,
            ]);

            $webPush->queueNotification($subscription, $payload);
        }

        foreach ($webPush->flush() as $report) {
            // Aquí limpias fallidos
            if (!$report->isSuccess()) {
                $endpoint = $report->getRequest()->getUri()->__toString();
                PushSubscription::where('endpoint', $endpoint)->delete();
            }
        }

        return true;
        // $auth = [
        //     'VAPID' => [
        //         'subject' => env('VAPID_SUBJECT'),
        //         'publicKey' => env('VAPID_PUBLIC_KEY'),
        //         'privateKey' => env('VAPID_PRIVATE_KEY'),
        //     ],
        // ];

        // $webPush = new WebPush($auth);

        // foreach (PushSubscription::all() as $sub) {
        //     $subscription = Subscription::create([
        //         'endpoint' => $sub->endpoint,
        //         'publicKey' => $sub->public_key,
        //         'authToken' => $sub->auth_token,
        //         'contentEncoding' => $sub->content_encoding,
        //     ]);

        //     $payload = json_encode([
        //         'title' => $title,
        //         'body' => $body,
        //     ]);

        //     $webPush->sendOneNotification($subscription, $payload);
        // }

        // foreach ($webPush->flush() as $report) {
        //     // puedes revisar errores aquí si quieres
        // }
    }
}