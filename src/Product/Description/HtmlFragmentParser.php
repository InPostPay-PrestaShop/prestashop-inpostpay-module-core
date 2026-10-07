<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Description;

final class HtmlFragmentParser
{
    private const DOCUMENT_PREFIX = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>';

    private const DOCUMENT_SUFFIX = '</body></html>';

    private const BODY_DEPTH = 2;

    private const MAX_DEPTH = 2000;

    /**
     * @throws \RuntimeException
     */
    public function parse(string $html): \DOMDocument
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $useInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $document->loadHTML(self::DOCUMENT_PREFIX . $html . self::DOCUMENT_SUFFIX, \LIBXML_NONET | \LIBXML_PARSEHUGE);
            $error = libxml_get_last_error();
            if (false !== $error && \LIBXML_ERR_FATAL === $error->level) {
                throw new \RuntimeException(\sprintf('libxml fatal error %d: %s', $error->code, trim($error->message)));
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($useInternalErrors);
        }

        $this->checkNestingDepth($document);

        return $document;
    }

    /**
     * @throws \RuntimeException
     */
    private function checkNestingDepth(\DOMDocument $document): void
    {
        $node = $document;
        $depth = 0;
        while (true) {
            if (null !== $node->firstChild) {
                $node = $node->firstChild;
                ++$depth;
            } else {
                while (null === $node->nextSibling) {
                    $node = $node->parentNode;
                    --$depth;
                    if (null === $node || $node instanceof \DOMDocument) {
                        return;
                    }
                }
                $node = $node->nextSibling;
            }

            if ($node instanceof \DOMElement && $depth - self::BODY_DEPTH > self::MAX_DEPTH) {
                throw new \RuntimeException('HTML nesting is too deep.');
            }
        }
    }
}
