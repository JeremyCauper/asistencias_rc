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

        JsonDB::table('ajustes')->where('user_id', $userId)->delete();

        JsonDB::table('ajustes')->insert([
            'user_id' => $userId,
            'settings' => $settings,
        ]);
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
