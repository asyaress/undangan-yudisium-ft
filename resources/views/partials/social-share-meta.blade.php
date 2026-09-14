@php
    $shareTitle = $shareTitle ?? $pageTitle ?? 'Undangan Yudisium FT UNMUL';
    $shareDescription = $shareDescription
        ?? 'Undangan resmi Yudisium Fakultas Teknik Universitas Mulawarman. Program Sarjana Angkatan 83 Periode 3 Tahun 2026.';
    $shareImagePath = public_path('undangan-og.jpg');
    $shareImageVersion = is_file($shareImagePath) ? filemtime($shareImagePath) : time();
    $shareImageUrl = $shareImageUrl ?? secure_url('undangan-og.jpg').'?v='.$shareImageVersion;
    $shareUrl = $shareUrl ?? url('/');
@endphp
<meta name="description" content="{{ $shareDescription }}" />
<meta property="og:type" content="website" />
<meta property="og:site_name" content="Undangan Yudisium FT UNMUL" />
<meta property="og:title" content="{{ $shareTitle }}" />
<meta property="og:description" content="{{ $shareDescription }}" />
<meta property="og:url" content="{{ $shareUrl }}" />
<meta property="og:locale" content="id_ID" />
<meta property="og:image" content="{{ $shareImageUrl }}" />
<meta property="og:image:secure_url" content="{{ $shareImageUrl }}" />
<meta property="og:image:type" content="image/jpeg" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta property="og:image:alt" content="Undangan Yudisium Fakultas Teknik Universitas Mulawarman" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $shareTitle }}" />
<meta name="twitter:description" content="{{ $shareDescription }}" />
<meta name="twitter:image" content="{{ $shareImageUrl }}" />
<link rel="image_src" href="{{ $shareImageUrl }}" />
