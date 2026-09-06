<?php

declare(strict_types=1);

namespace AzPays\Services;

class PriceService extends AbstractService
{
    /**
     * Retrieve the real-time price quote for a cryptocurrency symbol (e.g. "BTC", "ETH", "USDT").
     *
     * @param string $symbol
     * @return array<string, mixed>
     */
    public function getQuote(string $symbol): array
    {
        return $this->transport->get('/v1/prices/' . rawurlencode($symbol));
    }

    /**
     * Retrieve 24h candlestick OHLCV data for a symbol.
     *
     * @param string $symbol
     * @return array<mixed>
     */
    public function getCandlesticks(string $symbol): array
    {
        return $this->transport->get('/v1/prices/' . rawurlencode($symbol) . '/candles');
    }
}
