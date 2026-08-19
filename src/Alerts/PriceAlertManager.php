<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Alerts;

use Aicrion\IrCurrencyRateScraper\DTO\Rate;
use Aicrion\IrCurrencyRateScraper\DTO\RateCollection;

/**
 * Manages price threshold alerts and rate fluctuation watchers.
 */
final class PriceAlertManager
{
    /** @var array<string, array<string, mixed>> */
    private array $alerts = [];

    /**
     * Register a price threshold alert.
     *
     * @param string $symbolOrId Asset identifier (e.g. 'USD', 'IR18K', 'BTC').
     * @param float $targetPrice Price threshold.
     * @param string $operator Comparison operator: '>=', '<=', '>', '<', '=='.
     * @param callable|null $callback Optional callback function fn(Rate $rate, array $alert)
     * @return string Unique Alert ID.
     */
    public function addPriceAlert(
        string $symbolOrId,
        float $targetPrice,
        string $operator = '>=',
        ?callable $callback = null
    ): string {
        $alertId = 'alert_' . uniqid('', true);
        $this->alerts[$alertId] = [
            'id' => $alertId,
            'type' => 'price',
            'symbol' => strtoupper(trim($symbolOrId)),
            'target' => $targetPrice,
            'operator' => $operator,
            'callback' => $callback,
            'triggered' => false,
        ];

        return $alertId;
    }

    /**
     * Register a 24h percentage change threshold alert.
     *
     * @param string $symbolOrId Asset identifier (e.g. 'USD', 'BTC').
     * @param float $percentThreshold e.g. 5.0 (for +5%) or -3.0 (for -3%).
     * @param string $operator Comparison operator: '>=', '<=', '>', '<'.
     * @param callable|null $callback Optional callback function.
     * @return string Unique Alert ID.
     */
    public function addPercentAlert(
        string $symbolOrId,
        float $percentThreshold,
        string $operator = '>=',
        ?callable $callback = null
    ): string {
        $alertId = 'alert_' . uniqid('', true);
        $this->alerts[$alertId] = [
            'id' => $alertId,
            'type' => 'percent',
            'symbol' => strtoupper(trim($symbolOrId)),
            'target' => $percentThreshold,
            'operator' => $operator,
            'callback' => $callback,
            'triggered' => false,
        ];

        return $alertId;
    }

    /**
     * Check registered alerts against a given RateCollection or single Rate.
     *
     * @param RateCollection|Rate $data
     * @return array<int, array<string, mixed>> List of triggered alerts.
     */
    public function check($data): array
    {
        $collection = $data instanceof Rate
            ? new RateCollection([$data])
            : $data;

        $triggeredAlerts = [];

        foreach ($this->alerts as $id => &$alert) {
            $symbol = $alert['symbol'];
            $rate = $collection->get($symbol);

            if ($rate === null) {
                continue;
            }

            $isMatch = false;
            $currentValue = 0.0;

            if ($alert['type'] === 'price') {
                $currentValue = $rate->getPrice();
                $isMatch = $this->evaluate($currentValue, $alert['target'], $alert['operator']);
            } elseif ($alert['type'] === 'percent') {
                $changePercent = $rate->getChangePercent();
                if ($changePercent !== null) {
                    $currentValue = $changePercent;
                    $isMatch = $this->evaluate($currentValue, $alert['target'], $alert['operator']);
                }
            }

            if ($isMatch) {
                $alert['triggered'] = true;
                $triggeredInfo = [
                    'alert_id' => $id,
                    'type' => $alert['type'],
                    'symbol' => $symbol,
                    'rate' => $rate,
                    'current_value' => $currentValue,
                    'target_value' => $alert['target'],
                    'operator' => $alert['operator'],
                ];

                if (is_callable($alert['callback'])) {
                    ($alert['callback'])($rate, $triggeredInfo);
                }

                $triggeredAlerts[] = $triggeredInfo;
            }
        }

        return $triggeredAlerts;
    }

    public function removeAlert(string $alertId): bool
    {
        if (isset($this->alerts[$alertId])) {
            unset($this->alerts[$alertId]);
            return true;
        }
        return false;
    }

    public function clearAlerts(): void
    {
        $this->alerts = [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getAlerts(): array
    {
        return $this->alerts;
    }

    private function evaluate(float $actual, float $target, string $operator): bool
    {
        switch ($operator) {
            case '>=':
                return $actual >= $target;
            case '<=':
                return $actual <= $target;
            case '>':
                return $actual > $target;
            case '<':
                return $actual < $target;
            case '==':
            case '=':
                return abs($actual - $target) < 0.0001;
            default:
                return false;
        }
    }
}
