<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Description;

final class DescriptionSanitizer implements DescriptionSanitizerInterface
{
    private const ALLOWED_ELEMENTS = [
        'p' => true, 'span' => true, 'br' => true, 'ul' => true, 'li' => true,
        'h1' => true, 'h2' => true, 'h3' => true, 'h4' => true, 'h5' => true, 'h6' => true,
        'strong' => true, 'b' => true, 'i' => true, 'em' => true,
        'u' => true, 's' => true, 'del' => true, 'strike' => true,
    ];

    private const BLOCK_ELEMENTS = [
        'p' => true, 'ul' => true, 'li' => true,
        'h1' => true, 'h2' => true, 'h3' => true, 'h4' => true, 'h5' => true, 'h6' => true,
    ];

    private const STYLED_ELEMENTS = [
        'p' => true, 'span' => true, 'ul' => true, 'li' => true,
        'h1' => true, 'h2' => true, 'h3' => true, 'h4' => true, 'h5' => true, 'h6' => true,
    ];

    private const RENAMED_ELEMENTS = ['ol' => 'ul'];

    private const DROPPED_ELEMENTS = [
        'script' => true, 'style' => true, 'img' => true, 'iframe' => true, 'video' => true, 'audio' => true,
        'object' => true, 'embed' => true, 'applet' => true, 'input' => true, 'textarea' => true,
        'select' => true, 'button' => true, 'label' => true, 'legend' => true, 'noscript' => true,
        'template' => true, 'svg' => true, 'math' => true, 'canvas' => true, 'head' => true, 'title' => true,
        'meta' => true, 'link' => true, 'base' => true, 'picture' => true, 'source' => true, 'track' => true,
        'map' => true, 'plaintext' => true, 'xmp' => true, 'listing' => true, 'noembed' => true, 'noframes' => true,
    ];

    private const BLOCK_CONTAINERS = [
        'div' => true, 'section' => true, 'article' => true, 'header' => true, 'footer' => true, 'main' => true,
        'aside' => true, 'nav' => true, 'figure' => true, 'figcaption' => true, 'blockquote' => true,
        'address' => true, 'details' => true, 'summary' => true, 'dl' => true, 'dt' => true, 'dd' => true,
        'pre' => true, 'tr' => true, 'caption' => true, 'center' => true, 'table' => true, 'hgroup' => true,
        'hr' => true,
    ];

    private const TABLE_CELLS = ['td' => true, 'th' => true];

    private const TEXT_ALIGN_VALUES = [
        'left' => 'left', 'right' => 'right', 'center' => 'center', 'justify' => 'justify',
        'start' => '', 'end' => '', 'match-parent' => '', 'justify-all' => '',
        'inherit' => '', 'initial' => '', 'unset' => '', 'revert' => '', 'revert-layer' => '',
    ];

    private const TEXT_DECORATION_LINES = ['underline' => true, 'line-through' => true];

    private const WINDOWS_1252_CODE_POINTS = [
        0x80 => 0x20AC, 0x82 => 0x201A, 0x83 => 0x0192, 0x84 => 0x201E, 0x85 => 0x2026, 0x86 => 0x2020,
        0x87 => 0x2021, 0x88 => 0x02C6, 0x89 => 0x2030, 0x8A => 0x0160, 0x8B => 0x2039, 0x8C => 0x0152,
        0x8E => 0x017D, 0x91 => 0x2018, 0x92 => 0x2019, 0x93 => 0x201C, 0x94 => 0x201D, 0x95 => 0x2022,
        0x96 => 0x2013, 0x97 => 0x2014, 0x98 => 0x02DC, 0x99 => 0x2122, 0x9A => 0x0161, 0x9B => 0x203A,
        0x9C => 0x0153, 0x9E => 0x017E, 0x9F => 0x0178,
    ];

    private const DISALLOWED_CHARACTERS = '~[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+|\xEF\xB7[\x90-\xAF]|\xEF\xBF[\xBE\xBF]|[\xF0-\xF4][\x8F\x9F\xAF\xBF]\xBF[\xBE\xBF]~';

    private const MARKUP_DECLARATION = '~<!--(?:-?>|.*?(?:--!?>|$))|<!\[CDATA\[(.*?)(?:\]\]>|$)|<[!?][^>]*(?:>|$)~s';

    private const CHARACTER_REFERENCE = '~&(?:#(?<decimal>[0-9]+);?|#[xX](?<hex>[0-9a-fA-F]+);?|(?<named>[a-zA-Z][a-zA-Z0-9]*;))?~';

    private const PREFIXED_TAG_NAME = '~<(/?)([a-zA-Z][a-zA-Z0-9]*):(?=[a-zA-Z])~';

    private const STRAY_LESS_THAN = '~<(?!/?[a-zA-Z])~';

    private const BLOCK_TAG = '</?(?:p|ul|li|h[1-6])(?: style="[^"]*")?>';

    private const DOCUMENT_PREFIX = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>';

    private const DOCUMENT_SUFFIX = '</body></html>';

    private const MAX_DEPTH = 1024;

    public function sanitize(string $html): string
    {
        $html = $this->preprocess($html);
        if ('' === trim($html)) {
            return '';
        }

        $document = $this->parse($html);

        return $this->tidy($this->serializeChildren($document, 0));
    }

    private function preprocess(string $html): string
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

        return $this->checkPcreResult(preg_replace(self::STRAY_LESS_THAN, '&lt;', $html));
    }

    /**
     * @param string[] $match
     */
    private function normalizeCharacterReference(array $match): string
    {
        if ('' !== ($match['named'] ?? '')) {
            $reference = '&' . $match['named'];
            $decoded = html_entity_decode($reference, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
            if ($decoded === $reference) {
                return '&amp;' . $match['named'];
            }

            return str_replace(['&', '<', '>', '"', "'"], ['&#38;', '&#60;', '&#62;', '&#34;', '&#39;'], $decoded);
        }

        if ('' !== ($match['hex'] ?? '')) {
            $digits = ltrim($match['hex'], '0');

            return \strlen($digits) > 6 ? '' : $this->numericCharacterReference((int) hexdec($digits));
        }

        if ('' !== ($match['decimal'] ?? '')) {
            $digits = ltrim($match['decimal'], '0');

            return \strlen($digits) > 7 ? '' : $this->numericCharacterReference((int) $digits);
        }

        return '&amp;';
    }

    private function numericCharacterReference(int $codePoint): string
    {
        if (isset(self::WINDOWS_1252_CODE_POINTS[$codePoint])) {
            $codePoint = self::WINDOWS_1252_CODE_POINTS[$codePoint];
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

    private function parse(string $html): \DOMDocument
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $useInternalErrors = libxml_use_internal_errors(true);

        try {
            $document->loadHTML(self::DOCUMENT_PREFIX . $html . self::DOCUMENT_SUFFIX, \LIBXML_NONET | \LIBXML_PARSEHUGE);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($useInternalErrors);
        }

        return $document;
    }

    private function serializeChildren(\DOMNode $node, int $depth): string
    {
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $this->serializeNode($child, $depth);
        }

        return $html;
    }

    private function serializeNode(\DOMNode $node, int $depth): string
    {
        if ($node instanceof \DOMText) {
            return $this->serializeText($node->data);
        }

        if (!$node instanceof \DOMElement) {
            return '';
        }

        $name = strtolower($node->nodeName);
        if (isset(self::DROPPED_ELEMENTS[$name])) {
            return '';
        }

        if ('br' === $name) {
            return '<br>';
        }

        if ($depth >= self::MAX_DEPTH) {
            return $this->serializeText($node->textContent);
        }

        $content = $this->serializeChildren($node, $depth + 1);
        if (isset(self::RENAMED_ELEMENTS[$name])) {
            $name = self::RENAMED_ELEMENTS[$name];
        }

        if (isset(self::ALLOWED_ELEMENTS[$name])) {
            return $this->serializeAllowedElement($node, $name, $content);
        }

        if (isset(self::TABLE_CELLS[$name])) {
            return $content . ' ';
        }

        if (isset(self::BLOCK_CONTAINERS[$name])) {
            return $content . '<br>';
        }

        return $content;
    }

    private function serializeText(string $text): string
    {
        return htmlspecialchars($this->checkPcreResult(preg_replace('~[\t\n\f\r ]+~', ' ', $text)), \ENT_NOQUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }

    private function serializeAllowedElement(\DOMElement $element, string $name, string $content): string
    {
        if ('' === trim(str_replace('<br>', '', $content), ' ')) {
            return isset(self::BLOCK_ELEMENTS[$name]) ? '<br>' : $content;
        }

        $style = isset(self::STYLED_ELEMENTS[$name]) ? $this->filterStyle($element->getAttribute('style')) : '';
        if ('' !== $style) {
            return '<' . $name . ' style="' . $style . '">' . $content . '</' . $name . '>';
        }

        return 'span' === $name ? $content : '<' . $name . '>' . $content . '</' . $name . '>';
    }

    private function filterStyle(string $style): string
    {
        $declarations = [];
        foreach (explode(';', strtolower($style)) as $declaration) {
            $parts = explode(':', $declaration, 2);
            if (2 !== \count($parts)) {
                continue;
            }

            $property = trim($parts[0]);
            $value = trim($this->checkPcreResult(preg_replace('~!\s*important\s*$~', '', $parts[1])));

            if ('text-align' === $property && isset(self::TEXT_ALIGN_VALUES[$value])) {
                $declarations['text-align'] = self::TEXT_ALIGN_VALUES[$value];
            } elseif (('text-decoration' === $property || 'text-decoration-line' === $property) && '' !== $value) {
                $lines = array_intersect_key(array_flip($this->checkPcreResult(preg_split('~\s+~', $value))), self::TEXT_DECORATION_LINES);
                $declarations['text-decoration'] = implode(' ', array_keys($lines));
            }
        }

        $css = '';
        foreach ($declarations as $property => $value) {
            if ('' !== $value) {
                $css .= $property . ':' . $value . ';';
            }
        }

        return $css;
    }

    private function tidy(string $html): string
    {
        $html = $this->checkPcreResult(preg_replace('~ {2,}~', ' ', $html));
        $html = $this->checkPcreResult(preg_replace('~ ?(<br>|' . self::BLOCK_TAG . ') ?~', '$1', $html));
        $html = $this->checkPcreResult(preg_replace('~<br>(?=<br>)~', '', $html));
        $html = $this->checkPcreResult(preg_replace('~(' . self::BLOCK_TAG . ')<br>|<br>(?=' . self::BLOCK_TAG . ')~', '$1', $html));

        return $this->checkPcreResult(preg_replace('~^(?:<br>| )|(?:<br>| )$~', '', $html));
    }

    /**
     * @template T
     *
     * @param T|false|null $result
     *
     * @return T
     *
     * @throws \RuntimeException
     */
    private function checkPcreResult($result)
    {
        if (null === $result || false === $result) {
            throw new \RuntimeException(\sprintf('PCRE error %d.', preg_last_error()));
        }

        return $result;
    }
}
