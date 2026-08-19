<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Http;

use Aicrion\IrCurrencyRateScraper\Contracts\HttpClientInterface;
use Aicrion\IrCurrencyRateScraper\Exceptions\NetworkException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as PsrClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Adapter allowing users to plug in any PSR-18 compliant HTTP client (e.g. Guzzle, Symfony).
 */
final class Psr18HttpClientAdapter implements HttpClientInterface
{
    private PsrClientInterface $client;
    private RequestFactoryInterface $requestFactory;

    public function __construct(PsrClientInterface $client, RequestFactoryInterface $requestFactory)
    {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
    }

    public function get(string $url, array $headers = [], int $timeoutSeconds = 15): string
    {
        try {
            $request = $this->requestFactory->createRequest('GET', $url);
            foreach ($headers as $k => $v) {
                $request = $request->withHeader($k, $v);
            }

            $response = $this->client->sendRequest($request);
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 400) {
                throw new NetworkException("HTTP request to {$url} failed with status {$statusCode}", $statusCode, null, $statusCode);
            }

            return (string) $response->getBody();
        } catch (ClientExceptionInterface $e) {
            throw new NetworkException("PSR-18 HTTP error while requesting {$url}: {$e->getMessage()}", (int) $e->getCode(), $e);
        }
    }
}
