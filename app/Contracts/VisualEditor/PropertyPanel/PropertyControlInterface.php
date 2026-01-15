<?php

declare(strict_types=1);

namespace App\Contracts\VisualEditor\PropertyPanel;

/**
 * Interface for Property Panel Controls.
 *
 * Each control type (box-model, typography, color-picker, etc.) implements
 * this interface to provide consistent behavior across the Property Panel.
 *
 * ETAP_07f_P5 FAZA PP.1: Property Panel Infrastructure
 *
 * @package App\Contracts\VisualEditor\PropertyPanel
 */
interface PropertyControlInterface
{
    /**
     * Get the control type identifier.
     *
     * @return string Control type (e.g., 'box-model', 'typography', 'color-picker')
     */
    public function getType(): string;

    /**
     * Get the human-readable label.
     *
     * @return string Display label (e.g., 'Marginesy', 'Typografia', 'Kolor')
     */
    public function getLabel(): string;

    /**
     * Get CSS properties this control manages.
     *
     * @return array<string> List of CSS properties (kebab-case)
     *   e.g., ['margin-top', 'margin-right', 'margin-bottom', 'margin-left']
     */
    public function getCssProperties(): array;

    /**
     * Get default value for this control.
     *
     * @return mixed Default value (type depends on control)
     *   - box-model: array with top/right/bottom/left
     *   - color-picker: string hex/rgba
     *   - typography: array with font-size, weight, etc.
     */
    public function getDefaultValue(): mixed;

    /**
     * Get Blade view path for this control.
     *
     * @return string View path (e.g., 'components.property-panel.controls.box-model')
     */
    public function getViewPath(): string;

    /**
     * Get additional options for this control.
     *
     * @return array Control-specific options:
     *   - presets: Predefined values
     *   - units: Available CSS units (px, rem, %, etc.)
     *   - min/max: Value constraints
     *   - step: Increment value
     */
    public function getOptions(): array;

    /**
     * Validate a value for this control.
     *
     * @param mixed $value Value to validate
     * @return bool True if valid
     */
    public function validate(mixed $value): bool;

    /**
     * Format value to CSS string.
     *
     * @param mixed $value Control value
     * @return string|array<string, string> CSS value(s)
     *   - Single property: string
     *   - Multiple properties: ['property' => 'value', ...]
     */
    public function format(mixed $value): string|array;

    /**
     * Parse CSS value(s) to control value format.
     *
     * @param string|array<string, string> $cssValue CSS value(s)
     * @return mixed Parsed control value
     */
    public function parse(string|array $cssValue): mixed;

    /**
     * Check if control supports responsive values.
     *
     * @return bool True if control supports Desktop/Tablet/Mobile variants
     */
    public function isResponsive(): bool;

    /**
     * Check if control supports hover states.
     *
     * @return bool True if control supports Normal/Hover variants
     */
    public function supportsHover(): bool;

    /**
     * Get control group/category.
     *
     * @return string Group name (e.g., 'Style', 'Layout', 'Advanced')
     */
    public function getGroup(): string;

    /**
     * Get control priority for ordering.
     *
     * @return int Priority (lower = higher position)
     */
    public function getPriority(): int;

    /**
     * Get icon identifier for the control.
     *
     * @return string|null Icon name or null
     */
    public function getIcon(): ?string;

    /**
     * Get tooltip/help text.
     *
     * @return string|null Help text or null
     */
    public function getTooltip(): ?string;

    /**
     * Check if control is readonly.
     *
     * @return bool True if control is display-only
     */
    public function isReadonly(): bool;

    /**
     * Get control configuration as array.
     *
     * @return array<string, mixed> Full control configuration
     */
    public function toArray(): array;
}
