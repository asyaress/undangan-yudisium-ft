@php
    $coverBannerUrl = \App\Support\InvitationBrand::coverBannerUrl();
@endphp
@once
<style>
    .cover-banner {
        width: min(100%, 720px);
        aspect-ratio: 16 / 9;
        margin: 0 auto 18px;
        overflow: hidden;
        border-radius: 20px;
        background: #103322;
        box-shadow: 0 12px 32px rgba(16, 51, 34, 0.18);
    }

    .cover-banner img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
    }

    .cover-banner-fallback {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 14px;
        width: 100%;
        height: 100%;
        padding: 16px 20px;
        background:
            radial-gradient(120% 80% at 100% 0%, rgba(196, 163, 90, 0.35), transparent 55%),
            linear-gradient(135deg, #0d2a1c 0%, #1f7a3a 58%, #164e2c 100%);
        color: #fff;
        text-align: left;
    }

    .cover-banner-fallback img {
        width: 64px;
        height: 64px;
        flex: 0 0 64px;
        object-fit: contain;
        filter: drop-shadow(0 6px 16px rgba(0, 0, 0, 0.25));
    }

    .cover-banner-fallback strong {
        display: block;
        font-size: clamp(16px, 3.4vw, 22px);
        font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1.15;
    }

    .cover-banner-fallback span {
        display: block;
        margin-top: 4px;
        color: rgba(255, 255, 255, 0.82);
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    @media (max-height: 720px) {
        .cover-banner {
            max-height: 28vh;
            aspect-ratio: auto;
            height: 28vh;
        }
    }

    @media (max-height: 560px) {
        .cover-banner {
            max-height: 22vh;
            height: 22vh;
            margin-bottom: 10px;
            border-radius: 14px;
        }

        .cover-banner-fallback img {
            width: 44px;
            height: 44px;
            flex-basis: 44px;
        }
    }
</style>
@endonce
<figure class="cover-banner{{ $coverBannerUrl ? ' has-image' : '' }}">
    @if ($coverBannerUrl)
        <img src="{{ $coverBannerUrl }}" alt="Cover Yudisium Fakultas Teknik Universitas Mulawarman" width="1920" height="1080">
    @else
        <div class="cover-banner-fallback">
            <img src="{{ asset('Unmul.png') }}" alt="" width="64" height="64">
            <div>
                <strong>Universitas Mulawarman</strong>
                <span>Fakultas Teknik</span>
            </div>
        </div>
    @endif
</figure>
