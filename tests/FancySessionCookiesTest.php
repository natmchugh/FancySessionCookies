<?php

declare(strict_types=1);


use Badcfe\FancySessionCookies;
use Badcfe\SameSite;
use PHPUnit\Framework\TestCase;

class FancySessionCookiesTest extends TestCase
{
    public function setUp(): void
    {
        error_reporting(E_ALL);
    }

    /**
     * Undocumented function
     *
     * @return array<int, string>
     */
    private function getCookieHeaders(): array
    {
        $headers = xdebug_get_headers();
        $cookieHeaders = array_filter($headers, function ($header) {
            return strpos($header, "Set-Cookie:") === 0;
        });
        return array_values($cookieHeaders);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetThirdPartyCookie(): void
    {
        session_name("session");
        session_set_cookie_params([
            "lifetime" => 7776000,
            "path" => "/",
            "domain" => "news.site",
            "secure" => true,
            "httponly" => true,
            "samesite" => "None"
        ]);
        FancySessionCookies::startNewSession(false);
        $cookieHeaders = $this->getCookieHeaders();
        header_remove();
        $expected = "Set-Cookie: __Secure-session=" . session_id() . "; Domain=news.site; Secure; Path=/; Max-Age=7776000; HttpOnly; SameSite=None;";
        $this->assertSame($expected, $cookieHeaders[0]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetThirdPartyCookiePartitioned(): void
    {
        session_name("session");
        session_set_cookie_params([
            "lifetime" => 7776000,
            "path" => "/",
            "domain" => "news.site",
            "secure" => true,
            "httponly" => true,
            "samesite" => "None"
        ]);
        FancySessionCookies::startNewSession();
        $cookieHeaders = $this->getCookieHeaders();
        header_remove();
        $expected = "Set-Cookie: __Secure-session=" . session_id() . "; Domain=news.site; Secure; Path=/; Max-Age=7776000; HttpOnly; SameSite=None; Partitioned;";
        $this->assertSame($expected, $cookieHeaders[0]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testStartNewFirstPartySession(): void
    {
        session_name("session");
        ini_set('session.cookie_samesite', "Lax");
        session_set_cookie_params([
            "lifetime" => 7776000,
            "path" => "/",
            "domain" => "news.site",
            "secure" => true,
            "httponly" => true,
        ]);
        FancySessionCookies::startNewSession();
        $cookieHeaders = $this->getCookieHeaders();
        header_remove();
        $expected = "Set-Cookie: __Secure-session=" . session_id() . "; Domain=news.site; Secure; Path=/; Max-Age=7776000; HttpOnly; SameSite=Lax;";
        $this->assertSame($expected, $cookieHeaders[0]);
    }
}
