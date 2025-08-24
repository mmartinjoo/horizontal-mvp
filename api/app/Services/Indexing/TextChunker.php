<?php

namespace App\Services\Indexing;

use Illuminate\Support\Collection;

class TextChunker
{
    // Normally, 1 token is ~4 characters
    // However, there are special situations such as e-mail addresses, sales data with numbers, etc, when the ratio is much lower (2-3)
    // We don't know what kind of data users will synchronize, so we apply some margin of safety
    const TOKEN_TO_CHAR_RATIO = 1;

    public function __construct(private int $maxTokens = 8000, private int $overlapTokens = 200)
    {
    }

    /**
     * @return Collection<string>
     */
    public function chunk(string $text): Collection
    {
        $maxChars = $this->maxTokens * self::TOKEN_TO_CHAR_RATIO;
        $overlapChars = $this->overlapTokens * self::TOKEN_TO_CHAR_RATIO;
        if (strlen($text) <= $maxChars) {
            return collect([$text]);
        }

        $chunks = [];
        $start = 0;
        while ($start < strlen($text)) {
            $end = $start + $maxChars;
            if ($end < strlen($text)) {
                $end = $this->findNaturalBreakpoint($text, $end, $start + ($maxChars * 0.8));
            }

            $chunk = substr($text, $start, $end - $start);
            $chunks[] = trim($chunk);
            $start = max($start + 1, $end - $overlapChars);
            if ($start >= strlen($text)) {
                break;
            }
        }
        return collect($chunks);
    }

    private function findNaturalBreakpoint(string $text, int $preferredEnd, int $minEnd): int
    {
        // Paragraph boundary
        $paragraphBreak = strrpos($text, "\n\n", $preferredEnd - strlen($text));
        if ($paragraphBreak !== false && $paragraphBreak >= $minEnd) {
            return $paragraphBreak;
        }

        // Sentence boundary
        $sentenceBreak = strrpos($text, '. ', $preferredEnd - strlen($text));
        if ($sentenceBreak !== false && $sentenceBreak >= $minEnd) {
            return $sentenceBreak + 1;
        }

        // Word boundary
        $wordBreak = strrpos($text, ' ', $preferredEnd - strlen($text));
        if ($wordBreak !== false && $wordBreak >= $minEnd) {
            return $wordBreak;
        }

        // Character boundary
        return $preferredEnd;
    }
}
