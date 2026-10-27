<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Description;

final class DescriptionSanitizer implements DescriptionSanitizerInterface
{
    private const BLOCK_ELEMENTS = [
        'p' => true, 'ul' => true, 'li' => true,
        'h1' => true, 'h2' => true, 'h3' => true, 'h4' => true, 'h5' => true, 'h6' => true,
    ];

    private const INLINE_ELEMENTS = [
        'span' => true, 'strong' => true, 'b' => true, 'i' => true, 'em' => true,
        'u' => true, 's' => true, 'del' => true, 'strike' => true,
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

    private const ELEMENT_SEPARATORS = [
        'div' => '<br>', 'section' => '<br>', 'article' => '<br>', 'header' => '<br>', 'footer' => '<br>',
        'main' => '<br>', 'aside' => '<br>', 'nav' => '<br>', 'figure' => '<br>', 'figcaption' => '<br>',
        'blockquote' => '<br>', 'address' => '<br>', 'details' => '<br>', 'summary' => '<br>', 'dl' => '<br>',
        'dt' => '<br>', 'dd' => '<br>', 'pre' => '<br>', 'tr' => '<br>', 'caption' => '<br>', 'center' => '<br>',
        'table' => '<br>', 'hgroup' => '<br>', 'hr' => '<br>', 'td' => ' ', 'th' => ' ',
    ];

    private const TEXT_ALIGN_VALUES = ['left' => true, 'right' => true, 'center' => true, 'justify' => true];

    private const TEXT_DECORATION_LINES = ['underline' => true, 'line-through' => true];

    private const MAX_DEPTH = 1024;

    public function sanitize(string $html): string
    {
        $html = (new MarkupNormalizer())->normalize($html);
        if ('' === trim($html)) {
            return '';
        }

        $document = (new HtmlFragmentParser())->parse($html);

        return $this->tidy($this->serializeChildren($document, 0));
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

        $name = $this->elementName($node);
        if (isset(self::DROPPED_ELEMENTS[$name])) {
            return '';
        }

        if ('br' === $name) {
            return '<br>';
        }

        if ($depth >= self::MAX_DEPTH) {
            return $this->serializeFlattened($node);
        }

        $content = $this->serializeChildren($node, $depth + 1);
        if (isset(self::BLOCK_ELEMENTS[$name]) || isset(self::INLINE_ELEMENTS[$name])) {
            return $this->serializeAllowedElement($node, $name, $content);
        }

        return $content . (self::ELEMENT_SEPARATORS[$name] ?? '');
    }

    private function serializeFlattened(\DOMElement $root): string
    {
        $html = '';
        $stack = [$root];
        while ([] !== $stack) {
            $node = array_pop($stack);
            if (\is_string($node)) {
                $html .= $node;
                continue;
            }

            if ($node instanceof \DOMText) {
                $html .= $this->serializeText($node->data);
                continue;
            }

            if (!$node instanceof \DOMElement) {
                continue;
            }

            $name = $this->elementName($node);
            if (isset(self::DROPPED_ELEMENTS[$name])) {
                continue;
            }

            if ('br' === $name) {
                $html .= '<br>';
            } elseif (isset(self::BLOCK_ELEMENTS[$name]) || isset(self::ELEMENT_SEPARATORS[$name])) {
                $html .= ' ';
                $stack[] = ' ';
            }

            for ($child = $node->lastChild; null !== $child; $child = $child->previousSibling) {
                $stack[] = $child;
            }
        }

        return $html;
    }

    private function elementName(\DOMElement $element): string
    {
        $name = strtolower($element->nodeName);

        return self::RENAMED_ELEMENTS[$name] ?? $name;
    }

    private function serializeText(string $text): string
    {
        return htmlspecialchars(strtr($text, "\t\n\f\r", '    '), \ENT_NOQUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }

    private function serializeAllowedElement(\DOMElement $element, string $name, string $content): string
    {
        $isBlock = isset(self::BLOCK_ELEMENTS[$name]);
        if ('' === trim(str_replace('<br>', '', $content), ' ')) {
            return $isBlock ? '<br>' : $content;
        }

        $style = $isBlock || 'span' === $name ? $this->filterStyle($element->getAttribute('style')) : '';
        if ('' !== $style) {
            return '<' . $name . ' style="' . $style . '">' . $content . '</' . $name . '>';
        }

        return 'span' === $name ? $content : '<' . $name . '>' . $content . '</' . $name . '>';
    }

    private function filterStyle(string $style): string
    {
        $declarations = [];
        foreach (explode(';', $this->toAsciiLowercase($style)) as $declaration) {
            $parts = explode(':', $declaration, 2);
            if (2 !== \count($parts)) {
                continue;
            }

            $property = trim($parts[0]);
            $value = trim($this->checkPcreResult(preg_replace('~!\s*important\s*$~', '', $parts[1])));
            if ('text-align' === $property && isset(self::TEXT_ALIGN_VALUES[$value])) {
                $declarations['text-align'] = 'text-align:' . $value . ';';
            } elseif ('text-decoration' === $property || 'text-decoration-line' === $property) {
                $lines = array_intersect_key(array_flip($this->checkPcreResult(preg_split('~\s+~', $value))), self::TEXT_DECORATION_LINES);
                if ([] !== $lines) {
                    $declarations['text-decoration'] = 'text-decoration:' . implode(' ', array_keys($lines)) . ';';
                }
            }
        }

        return implode('', $declarations);
    }

    private function toAsciiLowercase(string $value): string
    {
        return strtr($value, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
    }

    private function tidy(string $html): string
    {
        $blockTag = '</?(?:' . implode('|', array_keys(self::BLOCK_ELEMENTS)) . ')(?: style="[^"]*")?>';
        $html = $this->checkPcreResult(preg_replace('~ {2,}~', ' ', $html));
        $html = $this->checkPcreResult(preg_replace('~ ?(<br>|' . $blockTag . ') ?~', '$1', $html));
        $html = $this->checkPcreResult(preg_replace('~<br>(?=<br>)~', '', $html));
        $html = $this->checkPcreResult(preg_replace('~(' . $blockTag . ')<br>|<br>(?=' . $blockTag . ')~', '$1', $html));

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
