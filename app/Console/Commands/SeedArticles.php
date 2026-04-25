<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedArticles extends Command
{
    protected $signature   = 'seed:articles {--file= : Path ke CSV (default: project root)}';
    protected $description = 'Import article_dataset5.csv ke tabel article di Supabase';

    public function handle(): int
    {
        $file = $this->option('file')
            ?? base_path('article_dataset5.csv');

        if (! file_exists($file)) {
            $this->error("File tidak ditemukan: {$file}");
            return self::FAILURE;
        }

        $this->info("Membaca: {$file}");

        // Baca CSV dengan fgetcsv
        $handle = fopen($file, 'r');
        $header = fgetcsv($handle); // baris pertama = header
        // header: article_id,title,content,photo_url,publish_date,url,section_id,view_count

        $batch  = [];
        $count  = 0;
        $batchSz = 50; // insert per 50 baris (Supabase limit-friendly)

        // Pastikan section records ada dulu
        $this->ensureSections();

        $this->info("Mengimport artikel...");
        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 8) continue;

            [$article_id, $title, $content, $photo_url,
             $publish_date, $url, $section_id, $view_count] = $row;

            $batch[] = [
                'article_id'   => (int) $article_id,
                'title'        => $title,
                'slug'         => \Illuminate\Support\Str::slug($title, '-'),
                'content'      => $content,
                'photo_url'    => $photo_url ?: null,
                'publish_date' => $publish_date ?: null,
                'url'          => $url ?: null,
                'section_id'   => $section_id ?: null,
                'view_count'   => (int) ($view_count ?? 0),
            ];

            if (count($batch) >= $batchSz) {
                DB::table('article')->upsert($batch, ['article_id'], [
                    'title','slug','content','photo_url',
                    'publish_date','url','section_id','view_count',
                ]);
                $count += count($batch);
                $batch  = [];
                $bar->advance($batchSz);
            }
        }

        // Sisa batch
        if (! empty($batch)) {
            DB::table('article')->upsert($batch, ['article_id'], [
                'title','slug','content','photo_url',
                'publish_date','url','section_id','view_count',
            ]);
            $count += count($batch);
            $bar->advance(count($batch));
        }

        fclose($handle);
        $bar->finish();

        $this->newLine();
        $this->info("✓ {$count} artikel berhasil diimport ke Supabase.");
        return self::SUCCESS;
    }

    private function ensureSections(): void
    {
        $sections = [
            ['section_id' => 'S001', 'section_name' => 'News',           'slug' => 'news'],
            ['section_id' => 'S002', 'section_name' => 'Entertainment',  'slug' => 'hiburan'],
            ['section_id' => 'S003', 'section_name' => 'Economy',        'slug' => 'ekonomi'],
            ['section_id' => 'S004', 'section_name' => 'Sports',         'slug' => 'olahraga'],
            ['section_id' => 'S005', 'section_name' => 'Automotive',     'slug' => 'otomotif'],
        ];

        foreach ($sections as $s) {
            DB::table('section')->updateOrInsert(
                ['section_id' => $s['section_id']],
                $s
            );
        }

        $this->line('  → Section records OK.');
    }
}
