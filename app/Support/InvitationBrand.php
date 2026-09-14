<?php

namespace App\Support;

class InvitationBrand
{
    public const UNIVERSITY = 'Universitas Mulawarman';

    public const FACULTY = 'Fakultas Teknik';

    public const EVENT = 'Yudisium';

    /**
     * Drop a 1920×1080 image in public/ as banner-cover.jpg (png/webp also accepted).
     */
    public static function coverBannerUrl(): ?string
    {
        foreach (['banner-cover.jpg', 'banner-cover.jpeg', 'banner-cover.png', 'banner-cover.webp'] as $file) {
            $path = public_path($file);
            if (is_file($path)) {
                return asset($file).'?v='.filemtime($path);
            }
        }

        return null;
    }
}
