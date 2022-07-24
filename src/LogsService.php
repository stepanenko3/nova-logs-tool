<?php

namespace Stepanenko3\LogsTool;

use Exception;
use Illuminate\Support\Facades\File;

class LogsService
{
    const MAX_FILE_SIZE = 52428800;

    /**
     * @var string file
     */
    private static string $file;

    private static array $levels_classes = [
        'debug'     => 'text-blue-500',
        'info'      => 'text-blue-500',
        'notice'    => 'text-blue-500',
        'warning'   => 'text-yellow-500',
        'error'     => 'text-red-500',
        'critical'  => 'text-red-500',
        'alert'     => 'text-red-500',
        'emergency' => 'text-red-500',
        'processed' => 'text-blue-500',
    ];

    private static array $levels_imgs = [
        'debug'     => 'exclamation-circle',
        'info'      => 'exclamation-circle',
        'notice'    => 'exclamation-circle',
        'warning'   => 'exclamation-triangle',
        'error'     => 'exclamation-triangle',
        'critical'  => 'exclamation-triangle',
        'alert'     => 'exclamation-triangle',
        'emergency' => 'exclamation-triangle',
        'processed' => 'exclamation-circle'
    ];

    /**
     * Log levels that are used.
     *
     * @var array
     */
    private static array $log_levels = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
        'processed'
    ];

    /**
     * @param  string  $file
     * @return string
     *
     * @throws Exception
     */
    public static function pathToLogFile($file): string
    {
        $file = storage_path('logs/' . $file);
        if (!File::exists($file)) {
            throw new Exception('No such log file');
        }

        return $file;
    }

    /**
     * @param $logFile
     * @return array
     * @throws Exception
     */
    public static function all($logFile): array
    {
        $log = [];
        $pattern = '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}([\+-]\d{4})?\].*/';

        if (!$logFile) {
            $log_file = self::getFiles();
            if (!count($log_file)) {
                return [];
            }
            $logFile = $log_file[0];
        }

        $logFile = self::pathToLogFile($logFile);

        if (File::size($logFile) > self::MAX_FILE_SIZE) {
            return $log;
        }

        $file = File::get($logFile);
        preg_match_all($pattern, $file, $headings);

        if (!is_array($headings)) {
            return $log;
        }

        $log_data = preg_split($pattern, $file);
        if ($log_data[0] < 1) {
            array_shift($log_data);
        }

        foreach ($headings as $h) {
            for ($i = 0, $j = count($h); $i < $j; $i++) {
                foreach (self::$log_levels as $level) {
                    if (strpos(strtolower($h[$i]), '.' . $level) || strpos(strtolower($h[$i]), $level . ':')) {
                        preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}([\+-]\d{4})?)\](?:.*?(\w+)\.|.*?)' . $level . ': (.*?)( in .*?:[0-9]+)?$/i', $h[$i], $current);
                        if (!isset($current[4])) {
                            continue;
                        }
                        $log[] = [
                            'context'     => $current[3],
                            'level'       => $level,
                            'level_class' => self::$levels_classes[$level],
                            'level_img'   => self::$levels_imgs[$level],
                            'date'        => $current[1],
                            'text'        => $current[4],
                            'in_file'     => $current[5] ?? null,
                            'stack'       => preg_replace("/^\n*/", '', $log_data[$i])
                        ];
                    }
                }
            }
        }

        return array_reverse($log);
    }

    /**
     * @return array
     */
    public static function getFiles(): array
    {
        $logsPath = storage_path('logs');
        $files = File::allFiles($logsPath);
        return array_reverse(collect($files)
            ->map(fn ($file) => $file->getRelativePathname())
            ->toArray());
    }
}
