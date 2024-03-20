<?php

namespace Stepanenko3\LogsTool;

use Closure;
use Illuminate\Http\Request;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\Tool;

class LogsTool extends Tool
{
    protected static $downloadCallback;

    protected static $deleteCallback;

    public static function authorizedToDownload(
        Request $request,
    ) {
        return static::$downloadCallback ? call_user_func(static::$downloadCallback, $request) : true;
    }

    public static function authorizedToDelete(
        Request $request,
    ) {
        return static::$deleteCallback ? call_user_func(static::$deleteCallback, $request) : true;
    }

    public function boot(): void
    {
        Nova::script('nova-logs', __DIR__ . '/../dist/js/tool.js');
        Nova::style('nova-logs', __DIR__ . '/../dist/css/tool.css');
    }

    public function menu(Request $request)
    {
        return MenuSection::make(__('Logs'))
            ->path('/logs')
            ->icon('document-text');
    }

    public function canDownload(
        Closure $closure,
    ): self {
        self::$downloadCallback = $closure;

        return $this;
    }

    public function canDelete(
        Closure $closure,
    ): self {
        self::$deleteCallback = $closure;

        return $this;
    }
}
