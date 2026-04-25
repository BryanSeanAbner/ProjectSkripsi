<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command(
    'import:dummy-data
    {--articles=article_dataset.csv : Path CSV article}
    {--acu=outputs/dummy_user_interaction_acu.csv : Path CSV user ACU}
    {--density=outputs/dummy_user_interaction_density.csv : Path CSV user density}',
    function () {
        $root = base_path();

        $paths = [
            'articles' => $this->option('articles'),
            'acu' => $this->option('acu'),
            'density' => $this->option('density'),
        ];

        foreach ($paths as $key => $path) {
            if (! str_starts_with($path, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:\\\\/', $path)) {
                $paths[$key] = $root.DIRECTORY_SEPARATOR.$path;
            }
        }

        $parseCsv = function (string $path, bool $required = true): array {
            if (! file_exists($path)) {
                if ($required) {
                    throw new RuntimeException("File tidak ditemukan: {$path}");
                }

                return [];
            }

            $handle = fopen($path, 'r');
            if ($handle === false) {
                throw new RuntimeException("Gagal membuka file: {$path}");
            }

            $header = fgetcsv($handle, 0, ',', '"', '');
            if ($header === false) {
                fclose($handle);
                return [];
            }

            $rows = [];
            while (($line = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if (count($line) !== count($header)) {
                    continue;
                }

                $rows[] = array_combine($header, $line);
            }

            fclose($handle);

            return $rows;
        };

        $upsertChunk = function (string $table, array $rows, array $uniqueBy, array $updateColumns): int {
            if (empty($rows)) {
                return 0;
            }

            $total = 0;
            foreach (array_chunk($rows, 1000) as $chunk) {
                DB::table($table)->upsert($chunk, $uniqueBy, $updateColumns);
                $total += count($chunk);
            }

            return $total;
        };

        DB::beginTransaction();

        try {
            $articleRows = $parseCsv($paths['articles'], true);
            $articleRows = array_map(function (array $row): array {
                return [
                    'article_id' => (int) $row['article_id'],
                    'title' => $row['title'],
                    'content' => $row['content'],
                    'photo_url' => $row['photo_url'] ?: null,
                    'publish_date' => $row['publish_date'] ?: null,
                    'url' => $row['url'] ?: null,
                    'section_id' => $row['section_id'],
                    'view_count' => (int) $row['view_count'],
                ];
            }, $articleRows);

            $acuRows = $parseCsv($paths['acu'], false);
            if (empty($acuRows)) {
                $this->warn("Lewati user_acu: file tidak ada / kosong ({$paths['acu']})");
            }
            $acuRows = array_map(function (array $row): array {
                return [
                    'interaction_id' => (int) $row['interaction_id'],
                    'article_id' => (int) $row['article_id'],
                    'user_id' => (int) $row['user_id'],
                    'interaction_timestamp' => $row['interaction_timestamp'],
                ];
            }, $acuRows);

            $densityRows = $parseCsv($paths['density'], false);
            if (empty($densityRows)) {
                $this->warn("Lewati user_density: file tidak ada / kosong ({$paths['density']})");
            }
            $densityRows = array_map(function (array $row): array {
                return [
                    'interaction_id' => (int) $row['interaction_id'],
                    'article_id' => (int) $row['article_id'],
                    'user_id' => (int) $row['user_id'],
                    'interaction_timestamp' => $row['interaction_timestamp'],
                ];
            }, $densityRows);

            $articleTotal = $upsertChunk(
                'articles',
                $articleRows,
                ['article_id'],
                ['title', 'content', 'photo_url', 'publish_date', 'url', 'section_id', 'view_count']
            );

            $acuTotal = $upsertChunk(
                'user_acu',
                $acuRows,
                ['interaction_id'],
                ['article_id', 'user_id', 'interaction_timestamp']
            );

            $densityTotal = $upsertChunk(
                'user_density',
                $densityRows,
                ['interaction_id'],
                ['article_id', 'user_id', 'interaction_timestamp']
            );

            DB::commit();

            $this->info("Import selesai: articles={$articleTotal}, user_acu={$acuTotal}, user_density={$densityTotal}");
            $this->line('Gunakan opsi --articles --acu --density jika lokasi file CSV berbeda.');
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error('Import gagal: '.$e->getMessage());

            return 1;
        }
    }
)->purpose('Import CSV article, user ACU, dan user density ke database');
