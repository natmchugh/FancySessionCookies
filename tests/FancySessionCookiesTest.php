<?php

declare(strict_types=1);


use Badcfe\FancySessionCookies;
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
        $expected = "/Set-Cookie: __Secure-session=" . session_id() . "; expires=(\w{3}), (\d{2}) (\w{3}) (\d{4}) (\d{2}):(\d{2}):(\d{2}) (\w{3}); Max-Age=7776000; path=\/; domain=news.site; secure; HttpOnly; SameSite=None/";
        $this->assertMatchesRegularExpression($expected, $cookieHeaders[0]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetThirdPartyCookieDefaultPartitioned(): void
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
        $expected = "/Set-Cookie: __Secure-session=" . session_id() . "; expires=(\w*), (\d{2})-(\w{3})-(\d{4}) (\d{2}):(\d{2}):(\d{2}) (\w{3}); Max-Age=7776000; path=\/; domain=news.site; secure; HttpOnly; SameSite=None; Partitioned/";
        $this->assertMatchesRegularExpression($expected, $cookieHeaders[0]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testStartNewFirstPartySession(): void
    {
        session_name("session");
        session_set_cookie_params([
            "lifetime" => 7776000,
            "path" => "/",
            "domain" => "news.site",
            "secure" => true,
            "httponly" => true,
            "samesite" => "Lax"
        ]);
        FancySessionCookies::startNewSession();
        $cookieHeaders = $this->getCookieHeaders();
        header_remove();
        $expected = "/Set-Cookie: __Secure-session=" . session_id() . "; expires=(\w{3}), (\d{2}) (\w{3}) (\d{4}) (\d{2}):(\d{2}):(\d{2}) (\w{3}); Max-Age=7776000; path=\/; domain=news.site; secure; HttpOnly; SameSite=Lax/";
        $this->assertMatchesRegularExpression($expected, $cookieHeaders[0]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testStartNewFirstPartySessionStrict(): void
    {
        session_name("session");
        session_set_cookie_params([
            "path" => "/",
            "secure" => true,
            "httponly" => true,
            "samesite" => "Strict"
        ]);
        FancySessionCookies::startNewSession();
        $cookieHeaders = $this->getCookieHeaders();
        header_remove();
        $expected = "Set-Cookie: __Host-session=" . session_id() . "; path=/; secure; HttpOnly; SameSite=Strict";
        $this->assertSame($expected, $cookieHeaders[0]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testStartNewSessionNoSameSite(): void
    {
        session_name("session");
        session_set_cookie_params([
            "path" => "/",
            "secure" => true,
            "httponly" => true,
        ]);
        FancySessionCookies::startNewSession();
        $cookieHeaders = $this->getCookieHeaders();
        header_remove();
        $expected = "Set-Cookie: __Host-session=" . session_id() . "; path=/; secure; HttpOnly";
        $this->assertSame($expected, $cookieHeaders[0]);
    }
}
