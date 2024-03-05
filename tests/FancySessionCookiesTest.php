<?php

declare(strict_types=1);


use Badcfe\FancySessionCookies;
use Badcfe\SameSite;
use PHPUnit\Framework\TestCase;

class FancySessionCookiesTest extends TestCase
{
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

    public function testSetName(): void
    {
        $isSecure = true;
        $path = "/";
        $name = "session";
        session_name($name);
        $expected = "__Host-session";
        FancySessionCookies::setName($isSecure, $path, "");
        $actual = session_name();
        $this->assertEquals($expected, $actual);
    }

    public function testGetName(): void
    {
        $expected = "session";
        $name = "session";
        session_name($name);
        $actual = FancySessionCookies::getName();
        $this->assertEquals($expected, $actual);
    }

    public function testBuildFirstPartyCookieString(): void
    {
        $name = "session";
        $id = "123";
        $params = [
            "secure" => true,
            "path" => "/",
            "lifetime" => 3600,
            "httponly" => true
        ];
        $sameSite = SameSite::Lax;
        $expected = "session=123; Secure; Path=/; Max-Age=3600; HttpOnly; SameSite=Lax;";
        $actual = FancySessionCookies::buildCookieString($name, $id, $params, $sameSite);
        $this->assertEquals($expected, $actual);
    }

    public function testBuildFirstPartyCookieWithDomainString(): void
    {
        $name = "session";
        $id = "123";
        $params = [
            "secure" => true,
            "path" => "/",
            "lifetime" => 3600,
            "httponly" => true
        ];
        $sameSite = SameSite::Lax;
        $expected = "session=123; Secure; Path=/; Max-Age=3600; HttpOnly; SameSite=Lax;";
        $actual = FancySessionCookies::buildCookieString($name, $id, $params, $sameSite);
        $this->assertEquals($expected, $actual);
    }

    public function testBuildThirdPartyCookieString(): void
    {
        $name = "session";
        $id = "123";
        $params = [
            "secure" => true,
            "path" => "/",
            "lifetime" => 3600,
            "httponly" => true
        ];
        $sameSite = SameSite::None;
        $expected = "session=123; Secure; Path=/; Max-Age=3600; HttpOnly; SameSite=None; Partitioned;";
        $actual = FancySessionCookies::buildCookieString($name, $id, $params, $sameSite);
        $this->assertEquals($expected, $actual);
    }

    public function testStartNewSession(): void
    {
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
        $expected = "Set-Cookie: __Secure-session=" . session_id() . "; Domain=news.site; Secure; Path=/; Max-Age=7776000; HttpOnly; SameSite=Lax;";
        $this->assertSame($expected, $cookieHeaders[0]);

    }
}
