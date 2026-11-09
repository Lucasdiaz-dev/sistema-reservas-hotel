<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Turns a login/registration return URL into a relative path of a real app route.
 * Rejects external hosts, protocol-relative URLs, and anything the router cannot match.
 */
class InternalTargetPath
{
    private const MAX_QUERY_LENGTH = 2000;

    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function normalize(Request $request, mixed $value): ?string
    {
        $route = $this->matchRoute($request, $value);
        if ($route === null) {
            return null;
        }

        return $route['url'];
    }

    public function isReservationConfirm(Request $request, ?string $url): bool
    {
        $route = $this->matchRoute($request, $url);

        return $route !== null && $route['name'] === 'app_reserva_confirmar';
    }

    /**
     * Relative confirm-reservation URL, or null when the value is not that flow.
     */
    public function reservationConfirmPath(Request $request, mixed $value): ?string
    {
        $route = $this->matchRoute($request, $value);
        if ($route === null || $route['name'] !== 'app_reserva_confirmar') {
            return null;
        }

        return $route['url'];
    }

    public function saved(Request $request, string $firewallName): mixed
    {
        if (!$request->hasSession()) {
            return null;
        }

        return $request->getSession()->get($this->sessionKey($firewallName));
    }

    public function remember(Request $request, string $firewallName, string $url): void
    {
        if (!$request->hasSession()) {
            return;
        }

        $request->getSession()->set($this->sessionKey($firewallName), $url);
    }

    public function forget(Request $request, string $firewallName): void
    {
        if (!$request->hasSession()) {
            return;
        }

        $request->getSession()->remove($this->sessionKey($firewallName));
    }

    /**
     * @return array{name: string, url: string}|null
     */
    private function matchRoute(Request $request, mixed $value): ?array
    {
        if (!\is_string($value) || $value === '' || \strlen($value) > 2048) {
            return null;
        }

        if (preg_match('/[\x00-\x1F\x7F\\\\]/', $value) === 1 || str_contains($value, '@')) {
            return null;
        }

        $isRelative = str_starts_with($value, '/') && !str_starts_with($value, '//');
        $lower = strtolower($value);
        $isHttp = str_starts_with($lower, 'http://') || str_starts_with($lower, 'https://');
        if (!$isRelative && !$isHttp) {
            return null;
        }

        $parts = parse_url($value);
        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        if (!$this->sameOrigin($request, $parts)) {
            return null;
        }

        $path = $parts['path'] ?? '';
        $decodedPath = rawurldecode($path);
        if (
            $decodedPath === ''
            || !str_starts_with($decodedPath, '/')
            || str_starts_with($decodedPath, '//')
            || str_contains($decodedPath, '//')
            || str_contains($decodedPath, '\\')
            || str_contains($decodedPath, "\0")
        ) {
            return null;
        }

        $query = $this->queryParameters($parts['query'] ?? null);
        if ($query === null) {
            return null;
        }

        $matched = $this->matchPath($decodedPath);
        if ($matched === null) {
            return null;
        }

        $name = $matched['_route'] ?? null;
        if (!\is_string($name) || $name === '' || str_starts_with($name, '_')) {
            return null;
        }

        $routeParams = [];
        foreach ($matched as $key => $param) {
            if (!\is_string($key) || str_starts_with($key, '_')) {
                continue;
            }
            if (!\is_scalar($param)) {
                return null;
            }
            $routeParams[$key] = $param;
            unset($query[$key]);
        }

        $url = $this->router->generate($name, $routeParams);
        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($query, '', '&');
        }

        return ['name' => $name, 'url' => $url];
    }

    /**
     * @param array<string, mixed> $parts
     */
    private function sameOrigin(Request $request, array $parts): bool
    {
        if (!isset($parts['host'])) {
            return !isset($parts['scheme']);
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!\in_array($scheme, ['http', 'https'], true) || $scheme !== $request->getScheme()) {
            return false;
        }

        if (strtolower((string) $parts['host']) !== strtolower($request->getHost())) {
            return false;
        }

        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        return (int) $port === $request->getPort();
    }

    /**
     * @return array<string, string>|null
     */
    private function queryParameters(mixed $query): ?array
    {
        if ($query === null || $query === '') {
            return [];
        }

        if (!\is_string($query) || \strlen($query) > self::MAX_QUERY_LENGTH) {
            return null;
        }

        $parsed = [];
        parse_str($query, $parsed);
        $safe = [];
        foreach ($parsed as $key => $param) {
            if (!\is_string($key) || !\is_scalar($param)) {
                continue;
            }
            $safe[$key] = (string) $param;
        }

        return $safe;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function matchPath(string $path): ?array
    {
        $context = $this->router->getContext();
        $method = $context->getMethod();
        $context->setMethod('GET');

        try {
            return $this->router->match($path);
        } catch (ResourceNotFoundException|MethodNotAllowedException) {
            return null;
        } finally {
            $context->setMethod($method);
        }
    }

    private function sessionKey(string $firewallName): string
    {
        return '_security.'.$firewallName.'.target_path';
    }
}
