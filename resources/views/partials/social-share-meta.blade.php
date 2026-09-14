@php
    $shareTitle = $shareTitle ?? $pageTitle ?? 'Undangan Yudisium FT UNMUL';
    $shareDescription = $shareDescription ?? $pageTitle ?? 'Undangan Yudisium Fakultas Teknik Universitas Mulawarman';
    $shareImageUrl = $shareImageUrl ?? secure_url('backdrop-fix.png');
    $shareUrl = $shareUrl ?? url()->current();
@endphp
<meta name="description" content="{{ $shareDescription }}" />
<meta property="og:type" content="website" />
<meta property="og:site_name" content="Undangan Yudisium FT UNMUL" />
<meta property="og:title" content="{{ $shareTitle }}" />
<meta property="og:description" content="{{ $shareDescription }}" />
<meta property="og:image" content="{{ $shareImageUrl }}" />
<meta property="og:url" content="{{ $shareUrl }}" />
<meta property="og:locale" content="id_ID" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $shareTitle }}" />
<meta name="twitter:description" content="{{ $shareDescription }}" />
<meta name="twitter:image" content="{{ $shareImageUrl }}" />
