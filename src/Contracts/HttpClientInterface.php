<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Contracts;

use Aicrion\IrCurrencyRateScraper\Exceptions\NetworkException;

interface HttpClientInterface
{
    /**
     * Send a GET request to the target URL and return response body as string.
     *
     * @param string $url
     * @param array<string, string> $headers
     * @param int $timeoutSeconds
     * @return string
     * @throws NetworkException
     */
    public function get(string $url, array $headers = [], int $timeoutSeconds = 15): string;
}
