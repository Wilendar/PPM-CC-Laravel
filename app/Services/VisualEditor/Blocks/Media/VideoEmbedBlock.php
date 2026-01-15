<?php

namespace App\Services\VisualEditor\Blocks\Media;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * Video Embed Block - YouTube/Vimeo embed with privacy facade.
 *
 * Supports lazy loading with click-to-play facade.
 */
class VideoEmbedBlock extends BaseBlock
{
    public string $type = 'video-embed';
    public string $name = 'Wideo (YouTube/Vimeo)';
    public string $icon = 'heroicons-play-circle';
    public string $category = 'media';

    /**
     * ETAP_07h PP.3: Property Panel controls for VideoEmbedBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['box-model', 'size', 'border'],
        '.pd-video__facade' => ['background', 'effects'],
        '.pd-video__thumbnail' => ['image-settings', 'effects'],
        '.pd-video__play-btn' => ['color-picker', 'size', 'effects'],
        'iframe' => ['border'],
    ];

    public array $defaultSettings = [
        'aspect_ratio' => '16/9',
        'autoplay' => false,
        'muted' => false,
        'loop' => false,
        'controls' => true,
        'lazy_facade' => true, // Privacy-friendly: show thumbnail, load on click
        'border_radius' => 'var(--pd-border-radius)',
        'max_width' => '100%',
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        $url = $content['url'] ?? '';
        $title = $this->escape($content['title'] ?? 'Video');

        if (!$url) {
            return '<!-- Video embed: no URL provided -->';
        }

        // Parse video info
        $videoInfo = $this->parseVideoUrl($url);

        if (!$videoInfo) {
            return '<!-- Video embed: unsupported URL format -->';
        }

        // Container classes
        $containerClasses = $this->classNames([
            'pd-block',
            'pd-video',
            "pd-video--{$videoInfo['provider']}",
            $settings['lazy_facade'] ? 'pd-video--facade' : '',
        ]);

        // Container styles
        $containerStyle = $this->inlineStyles([
            'aspect-ratio' => $settings['aspect_ratio'],
            'border-radius' => $settings['border_radius'],
            'max-width' => $settings['max_width'],
        ]);

        // Build embed or facade
        if ($settings['lazy_facade']) {
            return $this->buildFacade($videoInfo, $settings, $containerClasses, $containerStyle, $title);
        }

        return $this->buildEmbed($videoInfo, $settings, $containerClasses, $containerStyle, $title);
    }

    /**
     * Parse video URL and extract provider/ID.
     */
    private function parseVideoUrl(string $url): ?array
    {
        // YouTube
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return [
                'provider' => 'youtube',
                'id' => $matches[1],
                'thumbnail' => "https://img.youtube.com/vi/{$matches[1]}/maxresdefault.jpg",
            ];
        }

        // Vimeo
        if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches)) {
            return [
                'provider' => 'vimeo',
                'id' => $matches[1],
                'thumbnail' => '', // Vimeo thumbnails require API call
            ];
        }

        return null;
    }

    /**
     * Build privacy-friendly facade with thumbnail.
     */
    private function buildFacade(array $videoInfo, array $settings, string $classes, string $style, string $title): string
    {
        $thumbnail = $videoInfo['thumbnail'];
        $embedUrl = $this->buildEmbedUrl($videoInfo, $settings);

        return <<<HTML
        <div class="{$classes}" style="{$style}" data-embed-url="{$embedUrl}">
            <div class="pd-video__facade">
                <img src="{$thumbnail}" alt="{$title}" class="pd-video__thumbnail" loading="lazy" />
                <button type="button" class="pd-video__play-btn" aria-label="Odtworz wideo">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="64" height="64">
                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm14.024-.983a1.125 1.125 0 010 1.966l-5.603 3.113A1.125 1.125 0 019 15.113V8.887c0-.857.921-1.4 1.671-.983l5.603 3.113z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <noscript>
                <iframe src="{$embedUrl}" title="{$title}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </noscript>
        </div>
        HTML;
    }

    /**
     * Build direct embed iframe.
     */
    private function buildEmbed(array $videoInfo, array $settings, string $classes, string $style, string $title): string
    {
        $embedUrl = $this->buildEmbedUrl($videoInfo, $settings);

        return <<<HTML
        <div class="{$classes}" style="{$style}">
            <iframe
                src="{$embedUrl}"
                title="{$title}"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
                loading="lazy"
            ></iframe>
        </div>
        HTML;
    }

    /**
     * Build embed URL with parameters.
     */
    private function buildEmbedUrl(array $videoInfo, array $settings): string
    {
        $params = [];

        if ($settings['autoplay']) {
            $params['autoplay'] = '1';
        }
        if ($settings['muted']) {
            $params['mute'] = '1';
        }
        if ($settings['loop']) {
            $params['loop'] = '1';
        }
        if (!$settings['controls']) {
            $params['controls'] = '0';
        }

        $queryString = !empty($params) ? '?' . http_build_query($params) : '';

        return match ($videoInfo['provider']) {
            'youtube' => "https://www.youtube-nocookie.com/embed/{$videoInfo['id']}{$queryString}",
            'vimeo' => "https://player.vimeo.com/video/{$videoInfo['id']}{$queryString}",
            default => '',
        };
    }

    public function getSchema(): array
    {
        return [
            'content' => [
                'url' => [
                    'type' => 'url',
                    'label' => 'URL wideo (YouTube/Vimeo)',
                    'required' => true,
                    'placeholder' => 'https://www.youtube.com/watch?v=...',
                ],
                'title' => [
                    'type' => 'text',
                    'label' => 'Tytul (dostepnosc)',
                    'required' => false,
                ],
            ],
            'settings' => [
                'aspect_ratio' => [
                    'type' => 'select',
                    'label' => 'Proporcje',
                    'options' => [
                        '16/9' => 'Szerokie (16:9)',
                        '4/3' => 'Standard (4:3)',
                        '1/1' => 'Kwadrat (1:1)',
                        '21/9' => 'Ultra szerokie (21:9)',
                    ],
                    'default' => '16/9',
                ],
                'autoplay' => [
                    'type' => 'boolean',
                    'label' => 'Autoodtwarzanie',
                    'default' => false,
                ],
                'muted' => [
                    'type' => 'boolean',
                    'label' => 'Wyciszony',
                    'default' => false,
                ],
                'loop' => [
                    'type' => 'boolean',
                    'label' => 'Petla',
                    'default' => false,
                ],
                'controls' => [
                    'type' => 'boolean',
                    'label' => 'Kontrolki',
                    'default' => true,
                ],
                'lazy_facade' => [
                    'type' => 'boolean',
                    'label' => 'Fasada (prywatnosc)',
                    'default' => true,
                ],
                'border_radius' => [
                    'type' => 'text',
                    'label' => 'Zaokraglenie',
                    'default' => 'var(--pd-border-radius)',
                ],
                'max_width' => [
                    'type' => 'text',
                    'label' => 'Maksymalna szerokosc',
                    'default' => '100%',
                ],
            ],
        ];
    }
}
