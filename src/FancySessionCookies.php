<?php

declare(strict_types=1);

namespace Badcfe;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;

class FancySessionCookies
{
    private static function setName(bool $isSecure, string $path, string $domain): void
    {
        session_name(self::getPrefixedName(self::getName(), $isSecure, $path, $domain));
    }

    private static function getName(): string
    {
        $sessionName = session_name();
        return is_string($sessionName) ? $sessionName : "";
    }

    private static function alreadyContainsPrefix(string $name): bool
    {
        return strpos($name, "__Host-") === 0 || strpos($name, "__Secure-") === 0;
    }

    private static function getPrefixedName(string $name, bool $isSecure, string $path, string $domain): string
    {
        if (!self::alreadyContainsPrefix($name)) {
            if ($isSecure && $path === "/" && $domain === "") {
                return "__Host-" . $name;
            } elseif ($isSecure) {
                return "__Secure-" . $name;
            }
        }
        return $name;
    }

    /**
     *
     * @param string $name
     * @param string|false $id
     * @param array<string, mixed> $params
     * @param SameSite $sameSite
     * @return string
     */
    private static function buildCookieString(string $name, string|false $id, array $params, ?SameSite $sameSite, bool $partitioned): string
    {
        $cookieString = sprintf("%s=%s", $name, $id);
        $lifetime = $params['lifetime'];
        if (is_int($lifetime) && $lifetime > 0) {
            $date = new DateTimeImmutable();
            $newDate = $date->add(new DateInterval('PT' . $lifetime . "S"));
            $cookieString .= "; expires=".$newDate->format(DateTimeInterface::COOKIE);
            $cookieString .= "; Max-Age=$lifetime";
        }
        $path = $params['path'];
        if (is_string($path) && $path !== "") {
            $cookieString .= "; path=$path";
        }
        $domain = $params['domain'] ?? "";
        if (is_string($domain) && $domain !== "") {
            $cookieString .= "; domain=$domain";
        }
        $secure = $params['secure'];
        if ($secure === true) {
            $cookieString .= "; secure";
        }
        $httponly = $params['httponly'];
        if ($httponly === true) {
            $cookieString .= "; HttpOnly";
        }
        if ($sameSite instanceof SameSite) {
            $cookieString .= "; SameSite={$sameSite->value}";
        }
        if ($partitioned === true && $sameSite === SameSite::None) {
            $cookieString .= "; Partitioned";
        }
        return $cookieString;
    }

    public static function startNewSession(bool $partitionedIfThirdParty = true): void
    {
        $params  = session_get_cookie_params();
        self::setName($params['secure'], $params['path'], $params['domain']);
        $sameSite = SameSite::tryFrom($params['samesite']);
        $partitioned = $partitionedIfThirdParty && $sameSite === SameSite::None;
        if (session_start() && $partitioned) {
            header(
                sprintf(
                    'Set-Cookie: %s',
                    self::buildCookieString(
                        self::getName(),
                        session_id(),
                        $params,
                        $sameSite,
                        $partitioned
                    )
                )
            );
        }
    }
}
