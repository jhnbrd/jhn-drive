<?php

namespace App\Services;

use App\Support\SafePreview;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FileResponseService
{
    public function preview(Request $request, string $path, ?string $downloadName = null): Response
    {
        $filename = $downloadName ?? basename($path);
        $mime = SafePreview::mimeType($filename);

        if ($mime === null) {
            return response()->download($path, $filename, SafePreview::attachmentHeaders($filename));
        }

        $size = filesize($path);
        $file = fopen($path, 'rb');

        if ($size === false || $file === false) {
            abort(404, 'Preview unavailable.');
        }

        $headers = SafePreview::inlineHeaders($filename, $mime);
        $range = $request->header('Range');

        if ($range !== null && preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            $start = (int) $matches[1];
            $end = $matches[2] !== '' ? (int) $matches[2] : $size - 1;

            if ($start >= $size || $end >= $size || $start > $end) {
                fclose($file);

                return response('', 416, ['Content-Range' => "bytes */{$size}"]);
            }

            $length = $end - $start + 1;
            fseek($file, $start);
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
            $headers['Content-Length'] = (string) $length;

            return response()->stream(function () use ($file, $length): void {
                $sent = 0;

                while (! feof($file) && $sent < $length) {
                    $data = fread($file, min(65536, $length - $sent));
                    if ($data === false) {
                        break;
                    }

                    echo $data;
                    flush();
                    $sent += strlen($data);
                }

                fclose($file);
            }, 206, $headers);
        }

        $headers['Content-Length'] = (string) $size;

        return response()->stream(function () use ($file): void {
            fpassthru($file);
            fclose($file);
        }, 200, $headers);
    }
}
