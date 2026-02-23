<?php

namespace App\Services\Compatibility\Results;

class ModelDetectionResult
{
    public function __construct(
        public readonly bool $hasMatch,
        public readonly float $score,
        public readonly string $matchType = 'none',
        public readonly ?string $matchedAlias = null,
        public readonly int $tokenMatches = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'has_match' => $this->hasMatch,
            'score' => $this->score,
            'match_type' => $this->matchType,
            'matched_alias' => $this->matchedAlias,
            'token_matches' => $this->tokenMatches,
        ];
    }

    public static function noMatch(): self
    {
        return new self(false, 0.0);
    }
}
