<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Description;

final class MarkupNormalizer
{
    private const DISALLOWED_CHARACTERS = '~[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+|\xEF\xB7[\x90-\xAF]|\xEF\xBF[\xBE\xBF]|[\xF0-\xF4][\x8F\x9F\xAF\xBF]\xBF[\xBE\xBF]~';

    private const MARKUP_DECLARATION = '~<!--(?:-?>|[^-]*+(?:-(?!-!?>)[^-]*+)*+(?:--!?>|\z))|<!\[CDATA\[([^\]]*+(?:\](?!\]>)[^\]]*+)*+)(?:\]\]>|\z)|<[!?][^>]*+(?:>|\z)~s';

    private const CHARACTER_REFERENCE = '~&(?:#(?<decimal>[0-9]+);?|#[xX](?<hex>[0-9a-fA-F]+);?|(?<named>[a-zA-Z][a-zA-Z0-9]*;))?~';

    private const PREFIXED_TAG_NAME = '~<(/?)([a-zA-Z][a-zA-Z0-9]*):(?=[a-zA-Z])~';

    private const END_TAG_BR = '~</br(?=[\s/>])[^>]*+>~i';

    private const STRAY_LESS_THAN = '~<(?!/?[a-zA-Z])~';

    /**
     * @throws \RuntimeException
     */
    public function normalize(string $html): string
    {
        if (1 !== preg_match('//u', $html)) {
            $html = htmlspecialchars_decode(htmlspecialchars($html, \ENT_NOQUOTES | \ENT_IGNORE, 'UTF-8'), \ENT_NOQUOTES);
        }

        $html = $this->checkPcreResult(preg_replace(self::DISALLOWED_CHARACTERS, '', $html));
        $html = $this->checkPcreResult(preg_replace(self::MARKUP_DECLARATION, '$1', $html));
        $html = $this->checkPcreResult(preg_replace_callback(self::CHARACTER_REFERENCE, function (array $match): string {
            return $this->normalizeCharacterReference($match);
        }, $html));
        $html = $this->checkPcreResult(preg_replace(self::PREFIXED_TAG_NAME, '<$1$2-', $html));
        $html = $this->checkPcreResult(preg_replace(self::END_TAG_BR, '<br>', $html));

        return $this->checkPcreResult(preg_replace(self::STRAY_LESS_THAN, '&lt;', $html));
    }

    /**
     * @param string[] $match
     */
    private function normalizeCharacterReference(array $match): string
    {
        if ('' !== ($match['hex'] ?? '')) {
            $codePoint = \intval($match['hex'], 16);
        } elseif ('' !== ($match['decimal'] ?? '')) {
            $codePoint = (int) $match['decimal'];
        } else {
            return htmlspecialchars(html_entity_decode($match[0], \ENT_QUOTES | \ENT_HTML5, 'UTF-8'), \ENT_QUOTES, 'UTF-8');
        }

        return $this->isAllowedCodePoint($codePoint) ? '&#' . $codePoint . ';' : '';
    }

    private function isAllowedCodePoint(int $codePoint): bool
    {
        if ($codePoint < 0x20) {
            return 0x09 === $codePoint || 0x0A === $codePoint || 0x0D === $codePoint;
        }

        return ($codePoint < 0x7F || $codePoint > 0x9F)
            && ($codePoint < 0xD800 || $codePoint > 0xDFFF)
            && ($codePoint < 0xFDD0 || $codePoint > 0xFDEF)
            && 0xFFFE !== ($codePoint & 0xFFFE)
            && $codePoint <= 0x10FFFF;
    }

    /**
     * @throws \RuntimeException
     */
    private function checkPcreResult(?string $result): string
    {
        if (null === $result) {
            throw new \RuntimeException(\sprintf('PCRE error %d.', preg_last_error()));
        }

        return $result;
    }
}
