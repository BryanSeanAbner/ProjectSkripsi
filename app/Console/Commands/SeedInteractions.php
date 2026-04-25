<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SeedInteractions extends Command
{
    protected $signature   = 'seed:interactions {--file= : Path ke CSV}';
    protected $description = 'Import acu_interactions_customized5.csv ke user_interaction + buat users';

    public function handle(): int
    {
        $file = $this->option('file')
            ?? base_path('acu_interactions_customized5.csv');

        if (! file_exists($file)) {
            $this->error("File tidak ditemukan: {$file}");
            return self::FAILURE;
        }

        $this->info("Membaca: {$file}");

        $handle = fopen($file, 'r');
        fgetcsv($handle); // skip header: interaction_id,user_id,article_id

        $interactionBatch = [];
        $allUserIds       = [];
        $count            = 0;
        $batchSz          = 200;

        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) continue;

            [$interaction_id, $user_id, $article_id] = $row;

            $interactionBatch[] = [
                'interaction_id'        => (int) $interaction_id,
                'user_id'               => (int) $user_id,
                'article_id'            => (int) $article_id,
                'interaction_timestamp' => now()->toIsoString(),
            ];

            $allUserIds[(int) $user_id] = true;

            if (count($interactionBatch) >= $batchSz) {
                DB::table('user_interaction')->upsert(
                    $interactionBatch,
                    ['interaction_id'],
                    ['user_id', 'article_id', 'interaction_timestamp']
                );
                $count += count($interactionBatch);
                $interactionBatch = [];
                $bar->advance($batchSz);
            }
        }

        if (! empty($interactionBatch)) {
            DB::table('user_interaction')->upsert(
                $interactionBatch,
                ['interaction_id'],
                ['user_id', 'article_id', 'interaction_timestamp']
            );
            $count += count($interactionBatch);
            $bar->advance(count($interactionBatch));
        }

        fclose($handle);
        $bar->finish();
        $this->newLine();
        $this->info("✓ {$count} interaksi diimport.");

        // Buat users dari unique user_ids
        $this->info("Membuat users dari " . count($allUserIds) . " unique user IDs...");
        $defaultPassword = Hash::make('1'); // password default: '1'
        $genders = ['M', 'F'];
        $userBatch = [];

        foreach (array_keys($allUserIds) as $uid) {
            $userBatch[] = [
                'user_id'  => $uid,
                'username' => (string) $uid,
                'email'    => $uid . '@gmail.com',
                'password' => $defaultPassword,
                'gender'   => $genders[array_rand($genders)],
                'age'      => rand(18, 60),
            ];

            if (count($userBatch) >= 200) {
                DB::table('users')->upsert($userBatch, ['user_id'], ['username', 'email', 'password', 'gender', 'age']);
                $userBatch = [];
            }
        }

        if (! empty($userBatch)) {
            DB::table('users')->upsert($userBatch, ['user_id'], ['username', 'email', 'password', 'gender', 'age']);
        }

        $this->info("✓ " . count($allUserIds) . " users dibuat (username = user_id, password = '1', random age/gender).");
        return self::SUCCESS;
    }
}
