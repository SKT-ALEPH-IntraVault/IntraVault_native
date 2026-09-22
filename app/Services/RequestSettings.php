<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

/** One snapshot per HTTP request, never a session or process cache. */
class RequestSettings
{
    public static function enabled(string $table, string $id, bool $default): bool
    {
        $request=request();
        if (!$request->attributes->has('intravault.settings')) {
            return (bool) (DB::table($table)->where('id',$id)->value('enabled') ?? $default);
        }
        $cache=$request->attributes->get('intravault.settings');
        if (!array_key_exists($table,$cache)) {
            $cache[$table]=DB::table($table)->pluck('enabled','id')->all();
            $request->attributes->set('intravault.settings',$cache);
        }
        return (bool) ($cache[$table][$id] ?? $default);
    }

    public static function forget(): void
    {
        if (request()->attributes->has('intravault.settings')) request()->attributes->set('intravault.settings',[]);
    }
}
