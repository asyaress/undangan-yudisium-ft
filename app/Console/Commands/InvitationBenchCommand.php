<?php

namespace App\Console\Commands;

use App\Models\InvitationCategory;
use App\Models\YudisiumPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class InvitationBenchCommand extends Command
{
    protected $signature = 'invitation:bench
                            {--url= : Basis URL untuk HTTP (mis. https://domain). Kosongkan dengan --internal}
                            {--internal : Ukur PHP/Laravel langsung (disarankan di server production)}';

    protected $description = 'Ukur waktu (ms), ukuran HTML (KB), dan jumlah query untuk halaman undangan utama';

    public function handle(): int
    {
        $internal = (bool) $this->option('internal');
        $urlOption = $this->option('url');

        if ($urlOption === null && ! $internal) {
            $internal = true;
        }

        if (! $internal && ! app()->environment('local', 'testing')) {
            $this->warn('Bench HTTP ke domain publik dari server ini ikut latency DNS/TLS/jaringan.');
            $this->line('Untuk waktu render PHP saja, pakai: php artisan invitation:bench --internal');

            if (! $this->option('no-interaction') && ! $this->confirm('Lanjutkan bench HTTP?', false)) {
                return self::FAILURE;
            }
        }

        $paths = $this->benchPaths();
        $rows = [];

        foreach ($paths as $label => $path) {
            $rows[] = $internal
                ? $this->measureInProcess($path, $label)
                : $this->measureHttp(rtrim((string) $urlOption, '/').$path, $label);
        }

        $this->table(
            ['Halaman', 'ms', 'HTML KB', 'Catatan'],
            $rows,
        );

        $css = public_path('css/invitation.css');
        $js = public_path('js/invitation.js');
        $this->line(sprintf(
            'Aset statis: invitation.css %s KB, invitation.js %s KB',
            is_file($css) ? number_format(filesize($css) / 1024, 1) : '?',
            is_file($js) ? number_format(filesize($js) / 1024, 1) : '?',
        ));

        if ($internal) {
            $this->line('Mode: internal (kernel Laravel). Tambahkan ~50–150 KB unduhan CSS/JS di browser pertama kali.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function benchPaths(): array
    {
        $paths = [
            'Arsip (/)' => '/',
        ];

        $periodSlug = env('INVITATION_BENCH_PERIOD_SLUG');
        $period = null;

        if (is_string($periodSlug) && $periodSlug !== '') {
            $period = YudisiumPeriod::query()
                ->where('slug', $periodSlug)
                ->where('is_published', true)
                ->first();
        } else {
            $period = YudisiumPeriod::query()
                ->where('is_published', true)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();
            $periodSlug = $period?->slug;
        }

        if ($period && $periodSlug) {
            $categorySlug = InvitationCategory::query()
                ->where('period_id', $period->id)
                ->where('access_mode', InvitationCategory::ACCESS_NIM)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->value('slug') ?? 'yudisiawan';

            $paths['Gate mahasiswa'] = '/?event='.$periodSlug.'&to='.$categorySlug;
        } else {
            $this->line('Gate mahasiswa: tidak ada periode aktif / slug (set INVITATION_BENCH_PERIOD_SLUG jika perlu).');
        }

        return $paths;
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    private function measureHttp(string $url, string $label): array
    {
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
    private function measureInProcess(string $uri, string $label): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $request = \Illuminate\Http\Request::create($uri, 'GET');

        $start = hrtime(true);
        $response = $kernel->handle($request);
        $ms = round((hrtime(true) - $start) / 1_000_000, 1);
        $bytes = strlen($response->getContent() ?? '');
        $queries = count(DB::getQueryLog());
        $kernel->terminate($request, $response);

        return [
            $label,
            (string) $ms,
            number_format($bytes / 1024, 1),
            $queries.' query',
        ];
    }
}
