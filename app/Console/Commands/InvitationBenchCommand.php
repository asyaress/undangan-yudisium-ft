<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class InvitationBenchCommand extends Command
{
    protected $signature = 'invitation:bench {--url=http://127.0.0.1:8000}';

    protected $description = 'Ukur waktu (ms), ukuran HTML (KB), dan jumlah query untuk halaman undangan utama';

    public function handle(): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->warn('Jalankan di local atau set APP_ENV=local. Untuk production gunakan URL publik dengan hati-hati.');

            if (! $this->confirm('Lanjutkan?', false)) {
                return self::FAILURE;
            }
        }

        $base = rtrim((string) $this->option('url'), '/');
        $paths = [
            'Arsip (/)' => '/',
        ];

        $periodSlug = env('INVITATION_BENCH_PERIOD_SLUG');
        if (is_string($periodSlug) && $periodSlug !== '') {
            $paths['Gate mahasiswa'] = '/?event='.$periodSlug.'&to=yudisiawan';
        } else {
            $this->line('Tip: set INVITATION_BENCH_PERIOD_SLUG di .env untuk bench gate mahasiswa.');
        }

        $rows = [];

        foreach ($paths as $label => $path) {
            $rows[] = $this->measureHttp($base.$path, $label);
        }

        $this->table(
            ['Halaman', 'ms', 'HTML KB', 'Catatan'],
            $rows,
        );

        $css = public_path('css/invitation.css');
        $js = public_path('js/invitation.js');
        $this->line(sprintf('Aset statis: invitation.css %s KB, invitation.js %s KB',
            is_file($css) ? number_format(filesize($css) / 1024, 1) : '?',
            is_file($js) ? number_format(filesize($js) / 1024, 1) : '?',
        ));

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    private function measureHttp(string $url, string $label): array
    {
        if (str_starts_with($url, 'http://127.0.0.1') || str_starts_with($url, 'http://localhost')) {
            return $this->measureInProcess($url, $label);
        }

        $start = hrtime(true);
        $response = Http::timeout(30)->get($url);
        $ms = round((hrtime(true) - $start) / 1_000_000, 1);
        $bytes = strlen($response->body());

        return [
            $label,
            (string) $ms,
            number_format($bytes / 1024, 1),
            $response->successful() ? 'HTTP '.$response->status() : 'Gagal '.$response->status(),
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    private function measureInProcess(string $url, string $label): array
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);
        $uri = $path.($query ? '?'.$query : '');

        DB::flushQueryLog();
        DB::enableQueryLog();

        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $start = hrtime(true);
        $response = $kernel->handle(
            \Illuminate\Http\Request::create($uri, 'GET')
        );
        $ms = round((hrtime(true) - $start) / 1_000_000, 1);
        $bytes = strlen($response->getContent() ?? '');
        $queries = count(DB::getQueryLog());
        $kernel->terminate(
            \Illuminate\Http\Request::create($uri, 'GET'),
            $response
        );

        return [
            $label,
            (string) $ms,
            number_format($bytes / 1024, 1),
            $queries.' query',
        ];
    }
}
