<?php
declare(strict_types=1);

namespace Badcfe;

class FancySessionCookies
{
    public static function setName(bool $isSecure, string $path): void
    {
        session_name(self::getPrefixedName(session_name(), $isSecure, $path));
    }

    public static function getName(): string
    {
        return session_name();
    }

    private static function getPrefixedName(string $name, bool $isSecure, string $path): string
    {
        if ((strpos($name, "__Host-") || strpos($name, "__Secure-")) === false) {
            if ($isSecure && $path == "/") {
                return "__Host-" . $name;
            } elseif ($isSecure) {
                return "__Secure-" . $name;
            }
        }
        return $name;
    }

    public static function buildCookieString(string $name, string|false $id, array $params, SameSite $sameSite): string
    {
        $cookieString = sprintf("%s=%s;", $name, $id);
        $secure = $params['secure'];
        if ($secure === true) {
            $cookieString .= " Secure;";
        }
        $path = $params['path'];
        if ($path !== "") {
            $cookieString .= " Path=$path;";
        }
        $lifetime = $params['lifetime'];
        if ($lifetime > 0) {
            $cookieString .= " Max-Age=$lifetime;";
        }
        $httponly = $params['httponly'];
        if ($httponly === true) {
            $cookieString .= " HttpOnly;";
        }
        if ($sameSite === SameSite::None) {
            $cookieString .= " SameSite=None; Partitioned;";
        } else {
            $cookieString .= " SameSite=Lax;";
        }
        return $cookieString;
    }

    public static function startNewSession(): void
    {
        $params  = session_get_cookie_params();
        self::setName($params['secure'], $params['path']);
        session_start();
        $sameSite = SameSite::tryFrom($params['samesite']) ?? SameSite::Lax;
        header(
                sprintf('Set-Cookie: %s',
                self::buildCookieString(
                    self::getName(),
                    session_id(),
                    $params,
                    $sameSite
                )
            )
        );
    }
}
