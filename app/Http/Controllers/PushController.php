<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use function Laravel\Prompts\select;

class PushController extends Controller
{
    public function subscribe(Request $request)
    {
        $userId = Auth::user()->user_id;

        PushSubscription::whereNot('origin', $request->origin)->delete();

        PushSubscription::updateOrCreate(
            [
                'endpoint' => $request->endpoint,
            ],
            [
                'user_id' => $userId,
                'user_agent' => $request->userAgent(),
                'origin' => $request->origin,
                'public_key' => $request->keys['p256dh'],
                'auth_token' => $request->keys['auth'],
                'content_encoding' => $request->contentEncoding,
            ]
        );

        return ['success' => true];
    }

    public static function send($userId, $payload)
    {
        try {
            $subscriptions = PushSubscription::where('user_id', $userId)->get();

            if ($subscriptions->isEmpty())
                return false;

            $webPush = new \Minishlink\WebPush\WebPush([
                'VAPID' => [
                    'subject' => env('VAPID_SUBJECT'),
                    'publicKey' => env('VAPID_PUBLIC_KEY'),
                    'privateKey' => env('VAPID_PRIVATE_KEY'),
                ],
            ]);

            foreach ($subscriptions as $sub) {
                $subscription = \Minishlink\WebPush\Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->public_key,
                    'authToken' => $sub->auth_token,
                    'contentEncoding' => $sub->content_encoding,
                ]);

                $webPush->queueNotification($subscription, json_encode(array_merge($payload, [
                    'icon192' => secure_asset('front/images/app/icons/icon-192.png'),
                    'badge' => secure_asset('front/images/app/icons/icon-badge.png')
                ])));
            }

            foreach ($webPush->flush() as $report) {
                $endpoint = $report->getRequest()->getUri()->__toString();

                // Si NO tuvo éxito, lo borramos
                if (!$report->isSuccess()) {
                    PushSubscription::where('endpoint', $endpoint)->delete();
                    // Puedes loguearlo si deseas ver qué pasó:
                    Log::info("Suscripción eliminada: $endpoint, motivo: " . $report->getReason());
                }
            }

            return 'Notificaciones enviadas.';
        } catch (\Exception $e) {
            return false ? "" : "❌ Error al enviar: " . $e->getMessage();
        }
    }

    public static function sendForAdmin()
    {
        $area = Auth::user()->area_id;

        $ids = User::whereIn('rol_system', [5, 6])
            ->where('area_id', $area)
            ->orWhereIn('rol_system', [2, 4, 7])
            ->pluck('user_id');

        foreach ($ids as $id) {
            self::send($id, [
                'title' => 'Justificación pendiente',
                'body' => 'Tiene una nueva justificación pendiente de revisión.',
                'url' => secure_url('/asistencias-diarias'),
                'tag' => 'justificaciones',
            ]);
        }
    }

    public static function sendDerivado($id)
    {
        self::send(
            $id,
            [
                'title' => 'Derivación pendiente',
                'body' => 'Se registró una derivación, por favor subir su evidencia.',
                'url' => secure_url('/asistencias/misasistencias'),
                'tag' => 'derivaciones',
            ]
        );
    }

    public function test($id)
    {
        $this::send(
            $id,
            [
                'title' => 'Nueva justificación registrada',
                'body' => 'Tiene una nueva justificación pendiente de revisión.',
                'url' => secure_url('/asistencias/misasistencias'),
                'tag' => 'justificaciones',
            ]
        );
    }
}