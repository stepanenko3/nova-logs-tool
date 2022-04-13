<?php

namespace Stepanenko3\LogsTool;

use Illuminate\Support\Facades\File;

class LogsService
{
    const MAX_FILE_SIZE = 52428800;

    /**
     * @var string file
     */
    private static $file;

    private static $levels_classes = [
        'debug'     => 'text-blue',
        'info'      => 'text-blue',
        'notice'    => 'text-blue',
        'warning'   => 'text-orange',
        'error'     => 'text-red',
        'critical'  => 'text-red',
        'alert'     => 'text-red',
        'emergency' => 'text-red',
        'processed' => 'text-blue',
    ];

    private static $levels_imgs = [
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
    private static $log_levels = [
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
     * @throws \Exception
     */
    public static function pathToLogFile($file)
    {
        $file = storage_path('logs/' . $file);
        if (!File::exists($file)) {
            throw new \Exception('No such log file');
        }

        return $file;
    }

    /**
     * @return array
     */
    public static function all($logFile)
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
            return;
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
                            'in_file'     => isset($current[5]) ? $current[5] : null,
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
    public static function getFiles()
    {
        $logsPath = storage_path('logs');
        $files = File::allFiles($logsPath);
        return collect($files)
            ->map(fn ($file) => $file->getRelativePathname())
            ->toArray();
    }
}
