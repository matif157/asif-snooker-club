<?php

declare(strict_types=1);

namespace App\Services;

final class CsvService
{
    /**
     * Stream a CSV file to the browser.
     *
     * @param array<string,string> $headers  Column labels in display order.
     * @param array<int,array<string,mixed>> $rows  Associative rows keyed by column name.
     * @param array<string,string|callable> $columns  Optional output column name => source key (or callable($row)).
     */
    public static function download(string $filename, array $headers, array $rows, array $columns = []): void
    {
        // Excel-friendly UTF-8 BOM
        $out = fopen('php://output', 'w');
        if ($out === false) {
            http_response_code(500);
            exit;
        }

        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, array_values($headers), ',', '"', '\\');

        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $key => $_label) {
                $source = $columns[$key] ?? $key;

                if ($source instanceof \Closure) {
                    $value = $source($row);
                } elseif (is_string($source)) {
                    $value = $row[$source] ?? '';
                } else {
                    $value = $row[$key] ?? '';
                }

                $line[] = is_scalar($value) || $value === null ? (string) $value : json_encode($value);
            }
            fputcsv($out, $line, ',', '"', '\\');
        }

        fclose($out);
        exit;
    }

    public static function sendHeaders(string $filename): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}