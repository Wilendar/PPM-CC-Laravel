<?php

namespace App\Services\Compatibility\Results;

class BrandDetectionResult
{
    public function __construct(
        public readonly bool $hasMatch,
        public readonly float $score,
        public readonly string $matchSource = 'none',
    ) {}

    public function toArray(): array
    {
        return [
            'has_match' => $this->hasMatch,
            'score' => $this->score,
            'match_source' => $this->matchSource,
        ];
    }

    public static function noMatch(): self
    {
        return new self(false, 0.0);
    }
}
