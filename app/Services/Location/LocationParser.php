<?php

namespace App\Services\Location;

use Illuminate\Support\Collection;

class LocationParser
{
    /**
     * Regex patterns for location code detection.
     * Order matters - first match wins.
     */
    private const PATTERNS = [
        'coded' => '/^([A-Z]{1,2})_(\d{2})_(\d{2})$/',
        'dash'  => '/^([A-Z]{1,2})-(\d{2})-(\d{2})$/',
        'wall'  => '/^([A-Z])-SCIANA-(.+)$/',
        'gift'  => '/^([A-Z]{2,3})_(GIFT|GADZET|GIFTMAR)/',
        'named' => '/^[A-Z]{3,}$/',
    ];

    /**
     * Detect the pattern type for a given location code.
     */
    public function detectPattern(string $code): string
    {
        $upper = strtoupper(trim($code));

        foreach (self::PATTERNS as $type => $regex) {
            if (preg_match($regex, $upper)) {
                return $type;
            }
        }

        return 'other';
    }

    /**
     * Fully parse a location code into a structured result.
     */
    public function parse(string $code): LocationParseResult
    {
        $original = trim($code);
        $upper = strtoupper($original);
        $patternType = $this->detectPattern($upper);

        return match ($patternType) {
            'coded' => $this->parseCoded($original, $upper),
            'dash'  => $this->parseDash($original, $upper),
            'wall'  => $this->parseWall($original, $upper),
            'gift'  => $this->parseGift($original, $upper),
            'named' => $this->parseNamed($original, $upper),
            default => $this->parseOther($original, $upper),
        };
    }

    /**
     * Normalize a location code: uppercase, trim, convert dash codes to underscore format.
     */
    public function normalize(string $code): string
    {
        $upper = strtoupper(trim($code));
        $pattern = $this->detectPattern($upper);

        if ($pattern === 'dash') {
            return str_replace('-', '_', $upper);
        }

        return $upper;
    }

    /**
     * Build a hierarchical tree from a collection of location models.
     *
     * Input: Collection of models with a `code` attribute.
     * Output: Nested array grouped as Zone > Row > Shelf > Bin.
     *
     * @param  Collection  $locations  Collection of models with ->code property
     * @return array<string, array>
     */
    public function buildHierarchy(Collection $locations): array
    {
        $tree = [];

        foreach ($locations as $location) {
            $parsed = $this->parse($location->code);
            $zone = $parsed->zone ?? '__ungrouped__';

            if (! isset($tree[$zone])) {
                $tree[$zone] = ['_meta' => ['zone' => $zone], 'rows' => []];
            }

            $row = $parsed->rowCode ?? '__no_row__';

            if (! isset($tree[$zone]['rows'][$row])) {
                $tree[$zone]['rows'][$row] = ['_meta' => ['rowCode' => $row], 'shelves' => []];
            }

            if ($parsed->shelf === null) {
                $tree[$zone]['rows'][$row]['_items'][] = $parsed;
                continue;
            }

            $shelfKey = str_pad((string) $parsed->shelf, 2, '0', STR_PAD_LEFT);

            if (! isset($tree[$zone]['rows'][$row]['shelves'][$shelfKey])) {
                $tree[$zone]['rows'][$row]['shelves'][$shelfKey] = [
                    '_meta' => ['shelf' => $parsed->shelf],
                    'bins' => [],
                ];
            }

            if ($parsed->bin === null) {
                $tree[$zone]['rows'][$row]['shelves'][$shelfKey]['_items'][] = $parsed;
                continue;
            }

            $binKey = str_pad((string) $parsed->bin, 2, '0', STR_PAD_LEFT);
            $tree[$zone]['rows'][$row]['shelves'][$shelfKey]['bins'][$binKey] = $parsed;
        }

        ksort($tree);

        foreach ($tree as &$zoneData) {
            ksort($zoneData['rows']);

            foreach ($zoneData['rows'] as &$rowData) {
                ksort($rowData['shelves']);

                foreach ($rowData['shelves'] as &$shelfData) {
                    ksort($shelfData['bins']);
                }
            }
        }

        return $tree;
    }

    /**
     * Split a string with multiple location codes into an array.
     * Supports ';' and ',' as separators.
     */
    public function splitMultipleLocations(string $input): array
    {
        $parts = preg_split('/[;,]/', $input);

        return array_values(
            array_filter(
                array_map('trim', $parts),
                fn (string $part) => $part !== ''
            )
        );
    }

    // ------------------------------------------------------------------
    //  Private parsers per pattern type
    // ------------------------------------------------------------------

    private function parseCoded(string $original, string $upper): LocationParseResult
    {
        preg_match(self::PATTERNS['coded'], $upper, $m);

        $rowCode = $m[1];             // 'AA' or 'C'
        $zone = mb_substr($rowCode, 0, 1); // first letter
        $shelf = (int) $m[2];
        $bin = (int) $m[3];
        $normalized = $upper;
        $path = implode(' > ', [$zone, $rowCode, str_pad((string) $shelf, 2, '0', STR_PAD_LEFT), str_pad((string) $bin, 2, '0', STR_PAD_LEFT)]);

        return new LocationParseResult(
            originalCode: $original,
            normalizedCode: $normalized,
            patternType: 'coded',
            zone: $zone,
            rowCode: $rowCode,
            shelf: $shelf,
            bin: $bin,
            depth: 3,
            path: $path,
        );
    }

    private function parseDash(string $original, string $upper): LocationParseResult
    {
        preg_match(self::PATTERNS['dash'], $upper, $m);

        $rowCode = $m[1];
        $zone = mb_substr($rowCode, 0, 1);
        $shelf = (int) $m[2];
        $bin = (int) $m[3];
        $normalized = str_replace('-', '_', $upper);
        $path = implode(' > ', [$zone, $rowCode, str_pad((string) $shelf, 2, '0', STR_PAD_LEFT), str_pad((string) $bin, 2, '0', STR_PAD_LEFT)]);

        return new LocationParseResult(
            originalCode: $original,
            normalizedCode: $normalized,
            patternType: 'dash',
            zone: $zone,
            rowCode: $rowCode,
            shelf: $shelf,
            bin: $bin,
            depth: 3,
            path: $path,
        );
    }

    private function parseWall(string $original, string $upper): LocationParseResult
    {
        preg_match(self::PATTERNS['wall'], $upper, $m);

        $zone = $m[1];
        $position = $m[2];
        $path = implode(' > ', [$zone, 'SCIANA', $position]);

        return new LocationParseResult(
            originalCode: $original,
            normalizedCode: $upper,
            patternType: 'wall',
            zone: $zone,
            rowCode: 'SCIANA',
            shelf: null,
            bin: is_numeric($position) ? (int) $position : null,
            depth: 2,
            path: $path,
        );
    }

    private function parseGift(string $original, string $upper): LocationParseResult
    {
        preg_match(self::PATTERNS['gift'], $upper, $m);

        $prefix = $m[1];
        $giftType = $m[2];
        $zone = $prefix;
        $path = implode(' > ', [$prefix, $giftType]);

        return new LocationParseResult(
            originalCode: $original,
            normalizedCode: $upper,
            patternType: 'gift',
            zone: $zone,
            rowCode: $giftType,
            shelf: null,
            bin: null,
            depth: 1,
            path: $path,
        );
    }

    private function parseNamed(string $original, string $upper): LocationParseResult
    {
        return new LocationParseResult(
            originalCode: $original,
            normalizedCode: $upper,
            patternType: 'named',
            zone: $upper,
            rowCode: null,
            shelf: null,
            bin: null,
            depth: 0,
            path: $upper,
        );
    }

    private function parseOther(string $original, string $upper): LocationParseResult
    {
        return new LocationParseResult(
            originalCode: $original,
            normalizedCode: $upper,
            patternType: 'other',
            zone: null,
            rowCode: null,
            shelf: null,
            bin: null,
            depth: 0,
            path: $upper,
        );
    }
}
