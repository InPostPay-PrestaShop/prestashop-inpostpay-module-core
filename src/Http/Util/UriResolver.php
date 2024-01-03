<?php

declare(strict_types=1);

namespace izi\prestashop\Http\Util;

final class UriResolver
{
    public static function resolve(string $uri, string $baseUri): string
    {
        $uriParts = parse_url($uri);

        if (!empty($uriParts['host'])) {
            return $uri;
        }

        $baseUriParts = parse_url($baseUri);
        $uriParts['path'] = self::resolvePath($baseUriParts, $uriParts);
        $uriParts += $baseUriParts;

        return http_build_url($uriParts);
    }

    private static function resolvePath(array $baseUri, array $uri): string
    {
        $basePath = $baseUri['path'] ?? null;
        $path = $uri['path'] ?? null;

        if (null === $path) {
            $path = $basePath;
        } elseif ('/' !== $path[0]) {
            if (null === $basePath) {
                $path = '/' . $path;
            } else {
                $segments = explode('/', $basePath);
                array_splice($segments, -1, 1, [$path]);
                $path = implode('/', $segments);
            }
        }

        return empty($path) ? '/' : $path;
    }
}
