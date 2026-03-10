<?php

namespace App\Services\Location;

class LocationParseResult
{
    public function __construct(
        public readonly string $originalCode,
        public readonly string $normalizedCode,
        public readonly string $patternType,  // 'coded','dash','wall','named','gift','other'
        public readonly ?string $zone,
        public readonly ?string $rowCode,
        public readonly ?int $shelf,
        public readonly ?int $bin,
        public readonly int $depth,           // 0=zone, 1=row, 2=shelf, 3=bin
        public readonly ?string $path,        // "A > AA > 01 > 03"
    ) {}
}
