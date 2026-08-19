<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Http;

use Aicrion\IrCurrencyRateScraper\Contracts\HttpClientInterface;
use Aicrion\IrCurrencyRateScraper\Exceptions\NetworkException;

final class CurlHttpClient implements HttpClientInterface
{
    private string $userAgent;
    private int $defaultTimeout;
    /** @var array<string, string> */
    private array $defaultHeaders;
    private ?string $proxy = null;
    private ?string $proxyAuth = null;
    private bool $rotateUserAgent = false;
    /** @var string[] */
    private array $userAgentPool = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14.3; rv:123.0) Gecko/20100101 Firefox/123.0',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
    ];

    /**
     * @param array<string, string> $defaultHeaders
     */
    public function __construct(
        string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        int $defaultTimeout = 15,
        array $defaultHeaders = []
    ) {
        $this->userAgent = $userAgent;
        $this->defaultTimeout = $defaultTimeout;
        $this->defaultHeaders = array_merge([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language' => 'fa,en-US;q=0.9,en;q=0.8',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
        ], $defaultHeaders);
    }

    public function setProxy(?string $proxy, ?string $auth = null): self
    {
        $this->proxy = $proxy;
        $this->proxyAuth = $auth;
        return $this;
    }

    public function getProxy(): ?string
    {
        return $this->proxy;
    }

    public function enableUserAgentRotation(bool $enable = true): self
    {
        $this->rotateUserAgent = $enable;
        return $this;
    }

    /**
     * @param string[] $pool
     */
    public function setUserAgentPool(array $pool): self
    {
        if (!empty($pool)) {
            $this->userAgentPool = $pool;
        }
        return $this;
    }

    public function getActiveUserAgent(): string
    {
        if ($this->rotateUserAgent && !empty($this->userAgentPool)) {
            $idx = array_rand($this->userAgentPool);
            return $this->userAgentPool[$idx];
        }

        return $this->userAgent;
    }

    public function get(string $url, array $headers = [], int $timeoutSeconds = 0): string
    {
        $activeUserAgent = $this->getActiveUserAgent();

        if (!extension_loaded('curl')) {
            return $this->fallbackFileGetContents($url, $headers, $timeoutSeconds, $activeUserAgent);
        }

        $timeout = $timeoutSeconds > 0 ? $timeoutSeconds : $this->defaultTimeout;
        $mergedHeaders = array_merge($this->defaultHeaders, $headers);

        $formattedHeaders = [];
        foreach ($mergedHeaders as $k => $v) {
            $formattedHeaders[] = "{$k}: {$v}";
        }

        $ch = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 7,
            CURLOPT_USERAGENT => $activeUserAgent,
            CURLOPT_HTTPHEADER => $formattedHeaders,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING => '', // Accept all encodings (gzip, deflate, etc.)
        ];

        if ($this->proxy !== null && $this->proxy !== '') {
            $options[CURLOPT_PROXY] = $this->proxy;
            if ($this->proxyAuth !== null && $this->proxyAuth !== '') {
                $options[CURLOPT_PROXYUSERPWD] = $this->proxyAuth;
            }
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $errorNo = curl_errno($ch);
        $errorMsg = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (PHP_VERSION_ID < 80000) {
            /** @disregard P1006 */
            curl_close($ch);
        }

        if ($errorNo !== 0) {
            throw new NetworkException("cURL error ({$errorNo}) while requesting {$url}: {$errorMsg}", $errorNo);
        }

        if ($httpCode >= 400) {
            throw new NetworkException("HTTP error {$httpCode} while requesting {$url}", $httpCode, null, $httpCode);
        }

        if (!is_string($response)) {
            throw new NetworkException("Empty or invalid response received from {$url}");
        }

        return $response;
    }

    /**
     * @param array<string, string> $headers
     */
    private function fallbackFileGetContents(string $url, array $headers, int $timeoutSeconds, string $userAgent): string
    {
        $timeout = $timeoutSeconds > 0 ? $timeoutSeconds : $this->defaultTimeout;
        $mergedHeaders = array_merge($this->defaultHeaders, $headers);

        $headerLines = [];
        foreach ($mergedHeaders as $k => $v) {
            $headerLines[] = "{$k}: {$v}";
        }
        $headerLines[] = "User-Agent: {$userAgent}";

        $contextOptions = [
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headerLines),
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ];

        if ($this->proxy !== null && $this->proxy !== '') {
            $contextOptions['http']['proxy'] = $this->proxy;
            $contextOptions['http']['request_fulluri'] = true;
            if ($this->proxyAuth !== null && $this->proxyAuth !== '') {
                $headerLines[] = 'Proxy-Authorization: Basic ' . base64_encode($this->proxyAuth);
                $contextOptions['http']['header'] = implode("\r\n", $headerLines);
            }
        }

        $context = stream_context_create($contextOptions);

        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            $error = error_get_last();
            $msg = $error['message'] ?? 'Unknown network error';
            throw new NetworkException("Failed to fetch {$url} via stream context: {$msg}");
        }

        return $result;
    }
}
