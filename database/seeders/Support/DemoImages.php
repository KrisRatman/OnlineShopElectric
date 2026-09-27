<?php

namespace Database\Seeders\Support;

/**
 * Иллюстрации товаров для демо-каталога: SVG рисуется кодом, поэтому в репозитории
 * нет чужих фотографий, а у каждого товара своя картинка в своём цвете.
 */
final class DemoImages
{
    public static function make(string $type, string $accent, string $body = '#1e293b'): string
    {
        $content = match ($type) {
            'phone' => self::phone($accent, $body),
            'laptop' => self::laptop($accent, $body),
            'tower' => self::tower($accent, $body),
            'office' => self::office($accent, $body),
            'aio' => self::aio($accent, $body),
        };

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 600" width="800" height="600">
          <defs>
            <linearGradient id="screen" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0" stop-color="{$accent}"/>
              <stop offset="1" stop-color="#0f172a"/>
            </linearGradient>
            <linearGradient id="shine" x1="0" y1="0" x2="1" y2="0">
              <stop offset="0" stop-color="#ffffff" stop-opacity="0.22"/>
              <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
            </linearGradient>
            <radialGradient id="glow" cx="0.5" cy="0.5" r="0.5">
              <stop offset="0" stop-color="{$accent}" stop-opacity="0.35"/>
              <stop offset="1" stop-color="{$accent}" stop-opacity="0"/>
            </radialGradient>
          </defs>
          <ellipse cx="400" cy="560" rx="260" ry="18" fill="#0f172a" opacity="0.08"/>
          {$content}
        </svg>
        SVG;
    }

    private static function wallpaper(int $x, int $y, int $w, int $h): string
    {
        $y1 = $y + (int) ($h * 0.65);
        $y2 = $y + (int) ($h * 0.45);
        $y3 = $y + (int) ($h * 0.8);
        $xe = $x + $w;
        $ye = $y + $h;
        $xm = $x + (int) ($w / 2);

        return <<<SVG
          <path d="M{$x} {$y1} Q{$xm} {$y2} {$xe} {$y3} L{$xe} {$ye} L{$x} {$ye} Z" fill="#ffffff" opacity="0.10"/>
          <path d="M{$x} {$y3} Q{$xm} {$y1} {$xe} {$y2} L{$xe} {$ye} L{$x} {$ye} Z" fill="#ffffff" opacity="0.08"/>
        SVG;
    }

    private static function phone(string $accent, string $body): string
    {
        $wallpaper = self::wallpaper(292, 72, 172, 476);

        return <<<SVG
          <circle cx="430" cy="300" r="260" fill="url(#glow)"/>
          <g transform="rotate(8 470 310)">
            <rect x="400" y="70" width="190" height="500" rx="36" fill="{$body}"/>
            <rect x="400" y="70" width="190" height="500" rx="36" fill="url(#shine)"/>
            <rect x="418" y="90" width="84" height="96" rx="22" fill="#0f172a" opacity="0.55"/>
            <circle cx="442" cy="118" r="16" fill="#111827" stroke="#475569" stroke-width="4"/>
            <circle cx="478" cy="118" r="16" fill="#111827" stroke="#475569" stroke-width="4"/>
            <circle cx="442" cy="158" r="16" fill="#111827" stroke="#475569" stroke-width="4"/>
            <circle cx="480" cy="160" r="6" fill="#fef3c7"/>
          </g>
          <rect x="280" y="60" width="196" height="500" rx="36" fill="#0b1120"/>
          <rect x="292" y="72" width="172" height="476" rx="28" fill="url(#screen)"/>
          {$wallpaper}
          <rect x="348" y="86" width="60" height="18" rx="9" fill="#020617"/>
          <text x="378" y="190" text-anchor="middle" font-family="Arial, sans-serif" font-size="52" font-weight="700" fill="#ffffff" opacity="0.9">12:30</text>
        SVG;
    }

    private static function laptop(string $accent, string $body): string
    {
        $wallpaper = self::wallpaper(190, 112, 420, 262);

        return <<<SVG
          <circle cx="400" cy="260" r="280" fill="url(#glow)"/>
          <rect x="172" y="94" width="456" height="298" rx="18" fill="{$body}"/>
          <rect x="190" y="112" width="420" height="262" rx="6" fill="url(#screen)"/>
          {$wallpaper}
          <rect x="210" y="132" width="120" height="10" rx="5" fill="#ffffff" opacity="0.5"/>
          <rect x="210" y="152" width="80" height="10" rx="5" fill="#ffffff" opacity="0.3"/>
          <path d="M112 396 H688 L660 442 Q656 448 648 448 H152 Q144 448 140 442 Z" fill="#cbd5e1"/>
          <path d="M112 396 H688 L684 404 H116 Z" fill="#e2e8f0"/>
          <rect x="350" y="396" width="100" height="10" rx="5" fill="#94a3b8"/>
        SVG;
    }

    private static function tower(string $accent, string $body): string
    {
        $fans = '';

        foreach ([150, 270, 390] as $cy) {
            $fans .= <<<SVG
              <circle cx="360" cy="{$cy}" r="50" fill="none" stroke="{$accent}" stroke-width="7" opacity="0.95"/>
              <circle cx="360" cy="{$cy}" r="36" fill="{$accent}" opacity="0.18"/>
              <circle cx="360" cy="{$cy}" r="11" fill="{$accent}"/>
            SVG;
        }

        return <<<SVG
          <circle cx="400" cy="300" r="270" fill="url(#glow)"/>
          <rect x="250" y="56" width="300" height="490" rx="22" fill="{$body}"/>
          <rect x="266" y="74" width="212" height="454" rx="12" fill="#0b1120" stroke="#334155" stroke-width="2"/>
          {$fans}
          <rect x="286" y="460" width="172" height="44" rx="8" fill="#1e293b"/>
          <rect x="296" y="476" width="150" height="10" rx="5" fill="{$accent}" opacity="0.8"/>
          <rect x="266" y="74" width="212" height="454" rx="12" fill="url(#shine)"/>
          <rect x="494" y="74" width="40" height="454" rx="10" fill="#0f172a"/>
          <circle cx="514" cy="104" r="9" fill="none" stroke="{$accent}" stroke-width="3"/>
          <rect x="508" y="130" width="12" height="360" rx="6" fill="{$accent}" opacity="0.35"/>
          <rect x="262" y="546" width="40" height="12" rx="4" fill="#0f172a"/>
          <rect x="498" y="546" width="40" height="12" rx="4" fill="#0f172a"/>
        SVG;
    }

    private static function office(string $accent, string $body): string
    {
        $vents = '';

        for ($y = 150; $y <= 250; $y += 20) {
            $vents .= "<rect x=\"318\" y=\"{$y}\" width=\"164\" height=\"8\" rx=\"4\" fill=\"#0f172a\" opacity=\"0.25\"/>";
        }

        return <<<SVG
          <circle cx="400" cy="300" r="250" fill="url(#glow)"/>
          <rect x="290" y="80" width="220" height="460" rx="18" fill="{$body}"/>
          <rect x="290" y="80" width="220" height="460" rx="18" fill="url(#shine)"/>
          <circle cx="400" cy="118" r="14" fill="none" stroke="{$accent}" stroke-width="4"/>
          {$vents}
          <rect x="330" y="300" width="140" height="14" rx="7" fill="#0f172a" opacity="0.35"/>
          <rect x="330" y="326" width="40" height="14" rx="4" fill="#0f172a" opacity="0.35"/>
          <rect x="380" y="326" width="40" height="14" rx="4" fill="#0f172a" opacity="0.35"/>
          <rect x="330" y="480" width="140" height="6" rx="3" fill="{$accent}" opacity="0.8"/>
        SVG;
    }

    private static function aio(string $accent, string $body): string
    {
        $wallpaper = self::wallpaper(160, 74, 480, 270);

        return <<<SVG
          <circle cx="400" cy="250" r="280" fill="url(#glow)"/>
          <path d="M360 400 H440 L462 512 H338 Z" fill="#cbd5e1"/>
          <rect x="296" y="506" width="208" height="16" rx="8" fill="#94a3b8"/>
          <rect x="142" y="56" width="516" height="352" rx="20" fill="{$body}"/>
          <rect x="160" y="74" width="480" height="270" rx="6" fill="url(#screen)"/>
          {$wallpaper}
          <rect x="142" y="344" width="516" height="64" rx="0" fill="{$body}"/>
          <path d="M142 344 H658 V388 Q658 408 638 408 H162 Q142 408 142 388 Z" fill="{$body}"/>
          <path d="M142 344 H658 V388 Q658 408 638 408 H162 Q142 408 142 388 Z" fill="url(#shine)"/>
        SVG;
    }
}
