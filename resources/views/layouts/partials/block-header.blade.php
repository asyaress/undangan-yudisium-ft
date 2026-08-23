@php
    $breadcrumbParent = trim($__env->yieldContent('breadcrumb_parent'));
    $breadcrumbActive = trim($__env->yieldContent('breadcrumb_active')) ?: trim($__env->yieldContent('title'));
@endphp
<div class="block-header">
    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-12">
            <h2>@yield('title', 'Dashboard')</h2>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}"><i class="fa fa-dashboard"></i></a></li>
                @if ($breadcrumbParent !== '')
                    <li class="breadcrumb-item">{{ $breadcrumbParent }}</li>
                @endif
                <li class="breadcrumb-item active">{{ $breadcrumbActive }}</li>
            </ul>
        </div>
        @hasSection('page_actions')
            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="d-flex flex-row-reverse">
                    <div class="page_action">
                        @yield('page_actions')
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
