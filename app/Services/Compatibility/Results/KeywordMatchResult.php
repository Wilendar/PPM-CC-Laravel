<?php

namespace App\Services\Compatibility\Results;

class KeywordMatchResult
{
    public function __construct(
        public readonly bool $hasMatch,
        public readonly float $bonus,
        public readonly array $matchedRules = [],
        public readonly ?string $matchedKeyword = null,
    ) {}

    public function toArray(): array
    {
        return [
            'has_match' => $this->hasMatch,
            'bonus' => $this->bonus,
            'matched_rules_count' => count($this->matchedRules),
            'matched_keyword' => $this->matchedKeyword,
        ];
    }

    public static function noMatch(): self
    {
        return new self(false, 0.0);
    }
}
