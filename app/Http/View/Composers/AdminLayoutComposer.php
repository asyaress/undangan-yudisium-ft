<?php

namespace App\Http\View\Composers;

use App\Support\AdminDashboardCache;
use Illuminate\View\View;

class AdminLayoutComposer
{
    public function compose(View $view): void
    {
        $activePeriod = AdminDashboardCache::activePeriod();

        $view->with([
            'activePeriod' => $activePeriod,
            'adminPeriods' => AdminDashboardCache::periods(),
            'privateCategories' => AdminDashboardCache::privateCategories(),
            'sidebarStats' => AdminDashboardCache::sidebarStats($activePeriod?->id),
        ]);
    }
}
