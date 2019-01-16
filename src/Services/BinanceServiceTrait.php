<?php

namespace ph\sen\Services;

use ph\sen\Command\BaseCommand;
use App\Exchange\BinanceExchange;

trait BinanceServiceTrait
{

    public function binanceBuy($symbol = 'ADAUSDT', $uid, $price, $percent = '0%') {
        /** @var BinanceExchange $binance */
        $binance = $this->getExchange(1);
        $symbolInfo = $binance->getSymbolInfomation($symbol);
        $balance = round($binance->calculateQuantity($symbolInfo['quoteAsset'], '100%'), 1);
        // total USDT
        $quantity = round($this->calculateTargetQuantity($balance, $price, $percent), 1);
        $binance->buy($symbol, $quantity, $price);
    }

    public function binanceSell($symbol = 'ADAUSDT', $uid, $price, $percent = '0%') {
        /** @var BinanceExchange $binance */
        $binance = $this->getExchange(1);
        $symbolInfo = $binance->getSymbolInfomation($symbol);
        $balance = round($binance->calculateQuantity($symbolInfo['baseAsset'], '100%'), 1);
        // total ADA
        $quantity = $this->calculateBalancePercent($balance, $percent);
        $binance->sell($symbol, $quantity, $price);
        return round($quantity * $price, 2);
    }

    /**
     * TYPE = sell => count quantity of ADA, TYPE = buy => count quantity of USDT
     * @param string $symbol
     * @param $uid
     * @param string $type
     * @return bool
     */
    public function checkQuantity($symbol = 'ADAUSDT', $uid, $type = 'buy')
    {
        /** @var BinanceExchange $binance */
        $binance = $this->getExchange(1);
        $symbolInfo = $binance->getSymbolInfomation($symbol);
        $asset = ('buy' === $type) ? $symbolInfo['quoteAsset'] : $symbolInfo['baseAsset'];
        $balance = (int) $binance->calculateQuantity($asset, '100%');
        return ($balance > 5);
    }

    public function calculateTargetQuantity($balance, $price, $percent)
    {
        $balancePercent = $this->calculateBalancePercent($balance, $percent);
        return round($balancePercent / $price, 2);
    }

    public function calculateBalancePercent($balance, $percent)
    {
        $percent = BaseCommand::isPercent($percent);
        return ($percent / 100) * $balance;
    }
}
