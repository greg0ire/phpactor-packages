<?php

/**
 * This is used temporarily until bug is fixed in the Tolerant version:
 * https://github.com/Microsoft/tolerant-php-parser/pull/158
 */

namespace Phpactor\CodeBuilder\Adapter\TolerantParser;

class TextEdit
{
    /** @var int */
    public $start;

    /** @var int */
    public $length;

    /** @var string */
    public $content;

    public function __construct(int $start, int $length, string $content)
    {
        $this->start = $start;
        $this->length = $length;
        $this->content = $content;
    }

    /**
     * Applies array of edits to the document, and returns the resulting text.
     * Supplied $edits must not overlap, and be ordered by increasing start position.
     *
     * Note that after applying edits, the original AST should be invalidated.
     *
     * @param array | TextEdit[] $edits
     * @param string $text
     * @return string
     */
    public static function applyEdits(array $edits, string $text) : string
    {
        $prevEditStart = PHP_INT_MAX;
        for ($i = \count($edits) - 1; $i >= 0; $i--) {
            $edit = $edits[$i];

            if ($prevEditStart < $edit->start || $prevEditStart < $edit->start + $edit->length) {
                throw new \OutOfBoundsException(sprintf(
                    'Supplied TextEdit[] "%s" must not overlap and be in increasing start position order.',
                    $edit->content
                ));
            }

            if ($edit->start < 0 || $edit->length < 0 || $edit->start + $edit->length > \strlen($text)) {
                throw new \OutOfBoundsException("Applied TextEdit range out of bounds.");
            }
            $prevEditStart = $edit->start;
            $head = \substr($text, 0, $edit->start);
            $tail = \substr($text, $edit->start + $edit->length);
            $text = $head . $edit->content . $tail;
        }
        return $text;
    }
}
