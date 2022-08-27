<?php

namespace Stepanenko3\LogsTool;

use Illuminate\Http\Request;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\Tool;

class LogsTool extends Tool
{
    protected static $downloadCallback = null;
    protected static $deleteCallback = null;

    /**
     * Perform any tasks that need to happen when the tool is booted.
     *
     * @return void
     */
    public function boot()
    {
        Nova::script('nova-logs', __DIR__ . '/../dist/js/entry.js');
    }

    public function menu(Request $request)
    {
        return MenuSection::make('Logs')
            ->path('/logs')
            ->icon('document-text');
    }

    public static function authorizedToDownload(Request $request)
    {
        return static::$downloadCallback ? call_user_func(static::$downloadCallback, $request) : true;
    }

    public static function authorizedToDelete(Request $request)
    {
        return static::$deleteCallback ? call_user_func(static::$deleteCallback, $request) : true;
    }

    /**
     * @param  \Closure  $closure
     * @return $this
     */
    public function canDownload(\Closure $closure)
    {
        self::$downloadCallback = $closure;

        return $this;
    }

    /**
     * @param  \Closure  $closure
     * @return $this
     */
    public function canDelete(\Closure $closure)
    {
        self::$deleteCallback = $closure;

        return $this;
    }
}
