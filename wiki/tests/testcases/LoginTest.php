<?php

use PHPUnit\Framework\TestCase;

/**
 * Class LoginTest
 *
 * Test a local login (at localhost)
 */
class LoginTest extends TestCase
{
    /**
     * The login test
     */
    public function testLogin(): void
    {
        /* configurations */
        $username = getenv('TEST_USER_USERNAME');
        $password = getenv('TEST_USER_PASSWORD');
        $baseUrl = getenv('TEST_BASE_URL');
        $path1 = '/index.php/Test';
        $pathLogin = '/index.php/Special:Inloggning';

        /* make a cookie in the system temp-dir */
        $cookie = tempnam(sys_get_temp_dir(), 'cookie.');

        /** TODO: replace curl with an composer-package for http, like guzzlehttp/guzzle */
        /* set up curl envirement */
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_COOKIESESSION, true);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($c, CURLOPT_COOKIEJAR, $cookie);
        curl_setopt($c, CURLOPT_COOKIEFILE, $cookie);

        /* Get testpage, first time without an authenticated user */
        curl_setopt($c, CURLOPT_URL, $baseUrl . $path1);
        $page = curl_exec($c);
        $meta = curl_getinfo($c);

        /* test 1st response headers, part 1: http code */
        $this->assertArrayHasKey('http_code', $meta, 'No 1st HTTP-code');
        $this->assertStringStartsWith('403', $meta['http_code'], "Bad 1st HTTP-code, expected 403, got: '{$meta['http_code']}'");

        /* check so we not leaking data */
        $this->assertStringNotContainsString('Test, page exists ', $page, 'Page content visible for non authenticated');

        /* set & fetch login url, no post, we need a token först */
        curl_setopt($c, CURLOPT_URL, $baseUrl . $pathLogin);
        $page = curl_exec($c);
        $meta = curl_getinfo($c);

        /* test 2nd response headers, part 1: http code */
        $this->assertArrayHasKey('http_code', $meta, 'No 2nd HTTP-code');
        $this->assertStringStartsWith('2', $meta['http_code'], "Bad 2nd HTTP-code, expected 200, got: '{$meta['http_code']}'");

        $regexpLoginToken = '#<input name="wpLoginToken" type="hidden" value="(.*)"/>#';
        $regexpHit = preg_match($regexpLoginToken, $page, $matches);
        $this->assertSame($regexpHit, 1, 'wpLoginToken not found');
        $wpLoginToken = $matches[1];

        /* Login */
        $postData = [
            'authAction' => 'login',
            'wpName' => $username,
            'wpPassword' => $password,
            'wpLoginToken' => $wpLoginToken,
        ];
        curl_setopt($c, CURLOPT_POST, 1);
        curl_setopt($c, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
        /** @noinspection UnusedFunctionResultInspection we are only interested in the meta-data */
        curl_exec($c);
        $meta = curl_getinfo($c);
        curl_setopt($c, CURLOPT_POST, 0);
        curl_setopt($c, CURLOPT_POSTFIELDS, null);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, false);

        /* test 3th response headers, part 1: http code */
        $this->assertArrayHasKey('http_code', $meta, 'No 3th HTTP-code');
        $this->assertStringStartsWith('2', $meta['http_code'], "Bad 3th HTTP-code, expected 200, got: '{$meta['http_code']}'");

        /* Get testpage again, this with an authenticated user */
        curl_setopt($c, CURLOPT_URL, $baseUrl . $path1);
        $page = curl_exec($c);
        $meta = curl_getinfo($c);

        /* test 4th response headers, part 1: http code */
        $this->assertArrayHasKey('http_code', $meta, 'No 4th HTTP-code');
        $this->assertStringStartsWith('2', $meta['http_code'], "Bad 4th HTTP-code, expected 200, got: '{$meta['http_code']}'");

        $this->assertStringContainsString('Test, page exists', $page, 'Page content missing');
    }
}
