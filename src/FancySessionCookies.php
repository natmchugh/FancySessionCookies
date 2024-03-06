<?php

declare(strict_types=1);

namespace Badcfe;

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
     * Undocumented function
     *
     * @param string $name
     * @param string|false $id
     * @param array<string, mixed> $params
     * @param SameSite $sameSite
     * @return string
     */
    private static function buildCookieString(string $name, string|false $id, array $params, SameSite $sameSite, bool $partitioned): string
    {
        $cookieString = sprintf("%s=%s;", $name, $id);
        $domain = $params['domain'] ?? "";
        if (is_string($domain) && $domain !== "") {
            $cookieString .= " Domain=$domain;";
        }
        $secure = $params['secure'];
        if ($secure === true) {
            $cookieString .= " Secure;";
        }
        $path = $params['path'];
        if (is_string($path) && $path !== "") {
            $cookieString .= " Path=$path;";
        }
        $lifetime = $params['lifetime'];
        if (is_int($lifetime) && $lifetime > 0) {
            $cookieString .= " Max-Age=$lifetime;";
        }
        $httponly = $params['httponly'];
        if ($httponly === true) {
            $cookieString .= " HttpOnly;";
        }
        if ($sameSite === SameSite::None) {
            $cookieString .= " SameSite=None;";
            if ($partitioned) {
                $cookieString .= " Partitioned;";
            }
        } else {
            $cookieString .= " SameSite={$sameSite->value};";
        }
        return $cookieString;
    }

    public static function startNewSession(bool $partitioned = true): void
    {
        $params  = session_get_cookie_params();
        self::setName($params['secure'], $params['path'], $params['domain']);
        if (session_start()) {
            $sameSite = SameSite::tryFrom($params['samesite']) ?? SameSite::Lax;
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
