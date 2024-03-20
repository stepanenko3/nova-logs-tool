<?php

namespace Stepanenko3\LogsTool\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Stepanenko3\LogsTool\LogsTool;
use Stepanenko3\LaravelLogViewer\Facades\LogViewer;
use Stepanenko3\LaravelLogViewer\LogFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogsController extends Controller
{
    public function index(Request $request)
    {
        $currentPage = (int) LengthAwarePaginator::resolveCurrentPage();
        $perPage = (int) config('log-viewer.per_page');

        $file = (string) $request->input('file', '');
        $search = (string) $request->input('search', '');

        $selectedLevels = null;

        if ($request->has('selectedLevels')) {
            $selectedLevels = [];

            $tmpLevels = $request->input('selectedLevels');

            if ($tmpLevels) {
                $selectedLevels = explode(',', $tmpLevels);
            }
        }

        $files = LogFile::all()
            ->map(function ($file) {
                $file->sizeFormatted = $file->sizeFormatted();

                return $file;
            });

        if (!$file && $files->isNotEmpty()) {
            $file = $files->first()?->name;
        }

        if (!$file) {
            return response()->json([
                'files' => [],
                'file' => null,
            ]);
        }

        $selectedFile = LogFile::get(
            selectedFileName: $file,
            query: $search,
            selectedLevels: $selectedLevels,
            page: $currentPage,
            perPage: $perPage,
            direction: LogFile::NEWEST_FIRST,
        );

        return response()->json([
            'files' => $files,
            'file' => [
                'name' => $selectedFile['file']->name,
                'path' => $selectedFile['file']->path,
                'levels' => $selectedFile['levels'],
                'logs' => $selectedFile['logs'],
                'memoryUsage' => $selectedFile['memoryUsage'],
                'requestTime' => $selectedFile['requestTime'],
            ],
        ]);
    }

    public function cacheClear(Request $request)
    {
        if ($file = $request->input('file')) {
            LogViewer::getFile($file)->clearIndexCache();
        } else {
            LogViewer::clearCacheAll();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function download(
        $log,
        Request $request,
    ): BinaryFileResponse {
        if (!LogsTool::authorizedToDownload($request)) {
            abort(403);
        }

        return LogFile::download($log);
    }

    public function delete(
        Request $request,
    ): void {
        if (!LogsTool::authorizedToDelete($request)) {
            abort(403);
        }

        LogFile::deleteFile($request->input('file'));
    }

    public function permissions(Request $request)
    {
        return [
            'canDownload' => LogsTool::authorizedToDownload($request),
            'canDelete' => LogsTool::authorizedToDelete($request),
        ];
    }
}
