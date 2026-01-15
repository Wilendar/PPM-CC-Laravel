<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\BlockGenerator;

/**
 * BlockAnalysisResult - holds results of HTML analysis for block generation.
 *
 * ETAP_07f_P3: Visual Description Editor - Dedicated Blocks System
 *
 * CRITICAL: For PrestaShop templates (pd-* classes):
 * - isPrestaShopTemplate = true -> passthrough mode (no wrappers)
 * - CSS comes from PrestaShop custom.css, not inline styles
 */
class BlockAnalysisResult
{
    /**
     * Original HTML source.
     */
    public string $originalHtml = '';

    /**
     * Shop ID for the block.
     */
    public int $shopId = 0;

    /**
     * Suggested block name.
     */
    public string $suggestedName = '';

    /**
     * Suggested block type slug.
     */
    public string $suggestedType = '';

    /**
     * HTML structure analysis.
     */
    public array $structure = [];

    /**
     * Extracted CSS classes.
     */
    public array $cssClasses = [];

    /**
     * Detected repeating elements.
     */
    public array $repeaters = [];

    /**
     * Detected content fields.
     */
    public array $contentFields = [];

    /**
     * Generated schema.
     */
    public array $generatedSchema = [];

    /**
     * Generated render template.
     */
    public string $renderTemplate = '';

    /**
     * Analysis errors.
     */
    public array $errors = [];

    /**
     * Whether this is a PrestaShop template (has pd-* classes).
     * When true, block uses passthrough rendering (no wrappers).
     */
    public bool $isPrestaShopTemplate = false;

    /**
     * Detected PrestaShop section type (intro, cover, merits, etc.).
     */
    public string $prestaShopSectionType = 'block';

    /**
     * Check if analysis was successful.
     */
    public function isValid(): bool
    {
        return empty($this->errors) && !empty($this->renderTemplate);
    }

    /**
     * Get summary for display.
     */
    public function getSummary(): array
    {
        return [
            'name' => $this->suggestedName,
            'type' => $this->suggestedType,
            'rootTag' => $this->structure['rootTag'] ?? 'unknown',
            'elementCount' => $this->structure['elementCount'] ?? 0,
            'depth' => $this->structure['depth'] ?? 0,
            'cssClassCount' => count($this->cssClasses),
            'contentFieldCount' => count($this->contentFields),
            'repeaterCount' => count($this->repeaters),
            'hasImages' => $this->structure['hasImages'] ?? false,
            'hasLinks' => $this->structure['hasLinks'] ?? false,
            'errors' => $this->errors,
        ];
    }

    /**
     * Convert to array for JSON.
     */
    public function toArray(): array
    {
        return [
            'originalHtml' => $this->originalHtml,
            'shopId' => $this->shopId,
            'suggestedName' => $this->suggestedName,
            'suggestedType' => $this->suggestedType,
            'structure' => $this->structure,
            'cssClasses' => $this->cssClasses,
            'repeaters' => $this->repeaters,
            'contentFields' => $this->contentFields,
            'generatedSchema' => $this->generatedSchema,
            'renderTemplate' => $this->renderTemplate,
            'errors' => $this->errors,
            'isValid' => $this->isValid(),
            'isPrestaShopTemplate' => $this->isPrestaShopTemplate,
            'prestaShopSectionType' => $this->prestaShopSectionType,
        ];
    }
}
