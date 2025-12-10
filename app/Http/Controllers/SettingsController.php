<?php

namespace App\Http\Controllers;

use App\Services\JsonDB;
use Auth;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    protected static function bootSchema()
    {
        JsonDB::schema('ajustes', [
            'id' => 'int|primary_key|auto_increment',
            'user_id' => 'int|default:0',
            'settings' => 'array',
        ]);
    }

    public static function set($settings)
    {
        self::bootSchema();
        $userId = Auth::user()->user_id;
        $response = JsonDB::table('ajustes')->where('user_id', $userId)->first();

        if (!$response) {
            JsonDB::table('ajustes')->insert([
                'user_id' => $userId,
                'settings' => $settings,
            ]);
        } else {
            $payload = $response->settings;
            JsonDB::table('ajustes')->where('user_id', $userId)->update([
                'settings' => $payload,
            ]);
        }
    }

    public static function get()
    {
        if (!Auth::check()) {
            return;
        }
        self::bootSchema();
        $response = JsonDB::table('ajustes')->where('user_id', Auth::user()->user_id)->first();

        if ($response) {
            return $response->settings;
        }

        return null;
    }
}
