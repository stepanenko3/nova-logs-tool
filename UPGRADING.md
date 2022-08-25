# Upgrading

## To 2.0.0

### Step 1

Migrate from LogService to [laravel-log-viewer](https://github.com/stepanenko3/laravel-log-viewer)

### Step 2

Upgrade your Metric to
  
```php
<?php

namespace App\Nova\Metrics;

use Carbon\Carbon;
use Laravel\Nova\Metrics\MetricTableRow;
use Laravel\Nova\Metrics\Table;
use Stepanenko3\LaravelLogViewer\LogFile;

class LatestLogs extends Table
{
    private array $levels_classes = [
        'debug'     => 'text-sky-500',
        'info'      => 'text-sky-500',
        'notice'    => 'text-sky-500',
        'warning'   => 'text-yellow-500',
        'error'     => 'text-red-500',
        'critical'  => 'text-red-500',
        'alert'     => 'text-red-500',
        'emergency' => 'text-red-500',
        'processed' => 'text-sky-500',
    ];

    private array $level_icons = [
        'alert' => 'bell',
        'critical' => 'shield-exclamation',
        'debug' => 'code',
        'emergency' => 'speakerphone',
        'error' => 'exclamation-circle',
        'info' => 'information-circle',
        'notice' => 'annotation',
        'warning' => 'exclamation',
    ];

    public function __construct(
        protected ?string $file = null,
        protected int $countLastRow = 3,
    ) {
        //
    }

    public function calculate()
    {
        $logFile = LogFile::all()->first();

        $query = LogFile::get(
            selectedFileName: $this->file ?: $logFile->name,
            page: 1,
            perPage: $this->countLastRow,
            direction: LogFile::NEWEST_FIRST,
        );

        foreach ($query['logs'] as $line) {
            $rows[] = MetricTableRow::make()
                ->icon($this->level_icons[$line->level->value])
                ->iconClass($this->levels_classes[$line->level->value])
                ->title($line->text)
                ->subtitle(Carbon::create($line->time)->diffForHumans());
        }

        return $rows;
    }
}
```

Add Metric declaration code to Cards method of Dashboard class

```php
(new LatestLogs('laravel.log', 3)),
// Or show logs from last modified file
(new LatestLogs(null, 5)),
```
