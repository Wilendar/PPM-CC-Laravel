<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\VisualEditor\StylesetCompilerInterface;
use App\Services\VisualEditor\BlockRegistry;
use App\Services\VisualEditor\BlockRenderer;
use App\Services\VisualEditor\Styleset\StylesetCompiler;
use App\Services\VisualEditor\Styleset\StylesetFactory;
use App\Services\VisualEditor\Styleset\StylesetValidator;
use App\Services\VisualEditor\StylesetManager;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider for Visual Description Editor.
 *
 * Registers all services, singletons and bindings
 * required by the Visual Editor system.
 */
class VisualEditorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Styleset Services
        $this->app->singleton(StylesetValidator::class);

        $this->app->singleton(StylesetCompiler::class, function ($app) {
            return new StylesetCompiler(
                $app->make(StylesetValidator::class)
            );
        });

        $this->app->bind(StylesetCompilerInterface::class, StylesetCompiler::class);

        $this->app->singleton(StylesetFactory::class);

        $this->app->singleton(StylesetManager::class);

        // Block Services - use auto-discovery
        $this->app->singleton(BlockRegistry::class, function ($app) {
            $registry = new BlockRegistry();
            $registry->discoverBlocks();
            return $registry;
        });

        $this->app->singleton(BlockRenderer::class, function ($app) {
            return new BlockRenderer(
                $app->make(BlockRegistry::class),
                $app->make(StylesetManager::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config (optional)
        // $this->publishes([
        //     __DIR__.'/../../config/visual-editor.php' => config_path('visual-editor.php'),
        // ], 'visual-editor-config');

        // Load views
        // $this->loadViewsFrom(__DIR__.'/../../resources/views/visual-editor', 'visual-editor');
    }

    /**
     * Register default blocks with the registry.
     */
    private function registerDefaultBlocks(BlockRegistry $registry): void
    {
        // Layout Blocks
        $layoutBlocks = [
            \App\Services\VisualEditor\Blocks\Layout\HeroBannerBlock::class,
            \App\Services\VisualEditor\Blocks\Layout\TwoColumnBlock::class,
            \App\Services\VisualEditor\Blocks\Layout\ThreeColumnBlock::class,
            \App\Services\VisualEditor\Blocks\Layout\GridSectionBlock::class,
            \App\Services\VisualEditor\Blocks\Layout\FullWidthBlock::class,
        ];

        // Content Blocks
        $contentBlocks = [
            \App\Services\VisualEditor\Blocks\Content\HeadingBlock::class,
            \App\Services\VisualEditor\Blocks\Content\TextBlock::class,
            \App\Services\VisualEditor\Blocks\Content\FeatureCardBlock::class,
            \App\Services\VisualEditor\Blocks\Content\SpecTableBlock::class,
            \App\Services\VisualEditor\Blocks\Content\MeritListBlock::class,
            \App\Services\VisualEditor\Blocks\Content\InfoCardBlock::class,
        ];

        // Media Blocks
        $mediaBlocks = [
            \App\Services\VisualEditor\Blocks\Media\ImageBlock::class,
            \App\Services\VisualEditor\Blocks\Media\ImageGalleryBlock::class,
            \App\Services\VisualEditor\Blocks\Media\VideoEmbedBlock::class,
            \App\Services\VisualEditor\Blocks\Media\ParallaxImageBlock::class,
            \App\Services\VisualEditor\Blocks\Media\PictureElementBlock::class,
        ];

        // Interactive Blocks
        $interactiveBlocks = [
            \App\Services\VisualEditor\Blocks\Interactive\SliderBlock::class,
            \App\Services\VisualEditor\Blocks\Interactive\AccordionBlock::class,
            \App\Services\VisualEditor\Blocks\Interactive\TabsBlock::class,
            \App\Services\VisualEditor\Blocks\Interactive\CTAButtonBlock::class,
            \App\Services\VisualEditor\Blocks\Interactive\RawHtmlBlock::class,
        ];

        $allBlocks = array_merge(
            $layoutBlocks,
            $contentBlocks,
            $mediaBlocks,
            $interactiveBlocks
        );

        foreach ($allBlocks as $blockClass) {
            if (class_exists($blockClass)) {
                $registry->register(new $blockClass());
            }
        }
    }
}
