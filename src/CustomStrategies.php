<?php
/**
 * Created by PhpStorm.
 * User: joeldg
 * Date: 4/13/17
 * Time: 6:26 PM
 */

namespace ph\sen;

use Bowhead\Util\Util;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

trait CustomStrategies
{
    public function phuongb_bowhead_macd($pair, $data, $return_full = false, &$text = '')
    {
        $indicators = new Indicators();
        $rsi = $indicators->rsi($pair, $data, 14, $text); // 19 more accurate?
        $macd = trader_macd($data['close'], 12, 26, 9);
        $macd_raw = $macd[0];
        $signal = $macd[1];
        $hist = $macd[2];

        $macdCurrent = array_pop($macd_raw);
        $signalCurrent = array_pop($signal);
        $macd = $macdCurrent - $signalCurrent;

        // add 5 elements
        $histogram = $this->bowhead_5th_element($pair, $data, $return_full, $text);
//        $text = 'macd: ' . $macd . ' signal ' . $signalCurrent . ' histogram: ' . $histogram;
        /** macd */
        if ($macd < 0 || $histogram < 0) {
            $return['side'] = 'short';
            $return['strategy'] = 'rsi_macd';

            return ($return_full ? $return : -1);
        }
        if ($macd > 0) {
            $return['side'] = 'long';
            $return['strategy'] = 'rsi_macd';

            return ($return_full ? $return : 1);
        }

        return 0;
    }

    public function isSecondBuy($userData)
    {
        if (!isset($userData['buy_count'])) {
            return false;
        }

        if (1 <= $userData['buy_count']) {
            return false;
        }

        return true;
    }

    public function addBuyTime($pair, $data, &$userData)
    {
        // get indicate
        $indicators = new CustomIndicators();
        list($lastLastMfi, $lastMfi, $currentMfi) = $indicators->phuongMfis($pair, $data);
        $currentMfi = (int)$currentMfi;

        if ($currentMfi > 10) {
            return ;
        }

        if (!isset($userData['buy_count'])) {
            $userData['buy_count'] = 1;
            $userData['buy_time'] = date(self::LONG_TIME_STRING);
        }

        // skip if current time - minutes smaller than time order
        // below 4 hours
        $buyTime = $this->changeTimeStringToMilliSecond($userData['buy_time'], self::LONG_TIME_STRING);
        $currentTime = $this->changeTimeStringToMilliSecond(date(self::LONG_TIME_STRING), self::LONG_TIME_STRING);
        $currentTime = $this->reduceMilliSecondFromMinute($currentTime, 4 * 60);
        if ($currentTime >= $buyTime) {
            $userData['buy_count'] = $userData['buy_count'] + 1;
            $userData['buy_time'] = $this->changeTimeStringToMilliSecond($userData['buy_time'], self::LONG_TIME_STRING);
        }
    }

    public function phuongb_buy_second($pair, $data, $return_full = false, &$text = '')
    {
        $uid = 1;
        $user = $this->helper->user->findUserById($uid);
        $userData = json_decode($user->getData(), true);


        if ($this->isSecondBuy($userData)) {
            unset($userData['buy_count']);
            unset($userData['buy_time']);
            $user->setData(json_encode($userData));
            $this->helper->updateEntity($user);
            $text .= ' ready to buy (second_time) ';
            return 1;
        }

        // add and save data
        $this->addBuyTime($pair, $data, $userData);
        $user->setData(json_encode($userData));
        $this->helper->updateEntity($user);
        $count = isset($userData['buy_count']) ? $userData['buy_count'] : 0;
        $text .= ' current count ' . $count;
        return 0;
    }


    public function phuongb_mfi($pair, $data, $return_full = false, &$text = '')
    {
        $indicators = new CustomIndicators();
        list($lastLastMfi, $lastMfi, $currentMfi) = $indicators->phuongMfis($pair, $data);
        $currentMfi = (int)$currentMfi;
        if ($currentMfi >= 90) {
            $text .= ' current Mfi: ' . $currentMfi . ' ==> should sell ';
            return -1;
        }

        if ($currentMfi <= 10) {
            $text .= ' current Mfi: ' . $currentMfi . ' ==> should buy';
            return 1; // should buy
        }

        $text .= ' current Mfi: ' . $currentMfi . ' ==> normal';
        return 0;
    }

    public function phuongb_sell_stop_limit($pair, $data, $return_full = false, &$text = '')
    {
        $uid = 1;

        $ex = $this->helper->getExchange($uid);
        $activity = $this->helper->activity->findActivityByOutcome($uid, self::BUY);
        $beforeData = json_decode($activity->getData(), true);
        $data = ['before_buyer' => $beforeData['price'], 'current_price' => $ex->getCurrentPrice(self::SYMBOL)];
        $milliSecondBuyTime = $this->changeTimeStringToMilliSecond($beforeData['time'], self::LONG_TIME_STRING);
        $currentMilliSecondTime = $this->changeTimeStringToMilliSecond(date(self::LONG_TIME_STRING), self::LONG_TIME_STRING);
        $pastMilliSecondTime = $this->reduceMilliSecondFromMinute($currentMilliSecondTime, self::LIMITED_TIME);

        $percent = $ex->percentIncreate($data['before_buyer'], $data['current_price']);

        // current percent smaller than limited percent
        // and buy time below with past time = 60m
        if ($percent <= self::LIMITED_PERCENT && ($milliSecondBuyTime < $pastMilliSecondTime)) {
            $this->helper->user->blockUserById($uid);
            $text .= ' The user was stop-limited';
            return -1;
        }
        return 0;
    }

    public function phuongb_sell_profit_stop_limit($pair, $data, $return_full = false, &$text = '')
    {
        $uid = 1;

        $ex = $this->helper->getExchange($uid);
        $activity = $this->helper->activity->findActivityByOutcome($uid, self::BUY);
        $beforeData = json_decode($activity->getData(), true);
        $milliSecondBuyTime = $this->changeTimeStringToMilliSecond($beforeData['time'], self::LONG_TIME_STRING);
        $endMilliSecondTime = $this->changeTimeStringToMilliSecond(date(self::LONG_TIME_STRING), self::LONG_TIME_STRING);
        $startMilliSecondTime = $this->reduceMilliSecondFromMinute($endMilliSecondTime, 20 * $eachTime = 3);

        $candles = $this->binance->candlesticks(self::SYMBOL, '3m', $range = 50, $startMilliSecondTime, $endMilliSecondTime);

        // target = 1 percent from buy price
        $overTarget = false;
        foreach ($candles as $candle) {
            // skip if past with buy time
            if ($candle['closeTime'] < $milliSecondBuyTime) {
                continue;
            }

            $percent = $ex->percentIncreate($beforeData['price'], $candle['open']);
            if ($percent > self::PERCENT_TARGET_PROFIT) {
                $overTarget = true;
                break;
            }
        }

        $currentPrice = $ex->getCurrentPrice(self::SYMBOL);
        $profit = $ex->percentIncreate($beforeData['price'], $currentPrice);
        if ($overTarget && ($profit < self::PERCENT_LIMIT_PROFIT)) {
            $this->helper->user->blockUserById($uid);
            $text .= ' The user was stop-limited';
            return -1;
        }

        return 0;
    }

    public function phuongb_buy_stop_limit($pair, $data, $return_full = false, &$text = '')
    {
        $uid = 1;
        // normal with active user
        $user = $this->helper->user->findUserById($uid);
        if ($this->helper->user->isActiveUser($uid)) {
            return 1;
        }

        // case the user is blocked and below 50 then can not buy
        $indicators = new CustomIndicators();
        list($lastLastMfi, $lastMfi, $currentMfi) = $indicators->phuongMfis($pair, $data);
        if ($currentMfi < 70) {
            $text .= ' the user is blocked';
            return 0;
        }
        // Active the user
        $this->helper->user->activeUserById($uid);
        return 1;
    }

    public function phuongb_going_to_buy($pair, $data, $return_full = false, &$text = '')
    {
        $uid = 1;
        $activity = $this->helper->activity->findLatestActivity();
        $data = json_decode($activity->getData(), true);
        $ex = $this->helper->getExchange($uid);
        $currentPrice = $ex->getCurrentPrice(self::SYMBOL);
        $text .= ' current result: ' . $result = $this->getResults();

        if (false === $result) {
            return 0;
        }

        // clear price_going_buy
        if (isset($data['price_going_buy'])) {
            $data = $this->phuongb_minutes_to_clear_price_going_buy($data, self::TIME_TO_CLEAR_GOING_BUY, $text);
        }

        if (!isset($data['price_going_buy'])) {
            $data['price_going_buy'] = $currentPrice;
            $data['time_going_buy'] = date(self::LONG_TIME_STRING);
            $activity->setData(json_encode($data));
            $this->helper->updateEntity($activity);
            return 0;
        }

        $priceGoingBuy = $data['price_going_buy'];
        $profit = $ex->percentIncreate($priceGoingBuy, $currentPrice);
        // -0.5 < 0.1
        if (self::PERCENT_GOING_BUY < $profit) {
            $text .= ' can not buy because current profit: ' . $profit;
            return 0;
        }

        // -0.5% > -1%
        return 1;
    }

    public function phuongb_minutes_to_clear_price_going_buy($data, $minutes, &$text)
    {
        $millisecondGoingBuy = $this->changeTimeStringToMilliSecond($data['time_going_buy'], self::LONG_TIME_STRING);
        $millisecondCurrent = $this->changeTimeStringToMilliSecond(date(self::LONG_TIME_STRING), self::LONG_TIME_STRING);
        if ($millisecondGoingBuy < $this->reduceMilliSecondFromMinute($millisecondCurrent, $minutes)) {
            unset($data['price_going_buy']);
            $text .= ' price is cleared';
        }
        return $data;
    }

//    public function phuongb_vol($pair, $data, $return_full = false, &$text = '')
//    {
//        $indicators = new Indicators();
//        return $indicators->obv($pair, $data);
//        //        $indicators->mfi($pair, $data);
//    }

    public function phuongb_bowhead_sma($pair, $data, $return_full = false, &$text = '')
    {
        return $this->sma_maker($data['close'], 7);
    }

    public function phuongb_bowhead_stoch($pair, $data, $return_full = false, &$text = '')
    {
        $indicators = new Indicators();

        return $this->phuongbstoch($data, null, null, $text);
        //    if ($stoch < 0) {
        //      if ($adx == -1 && $bearish) {
        //        $return['side'] = 'short';
        //        $return['strategy'] = 'stoch_adx';
        //
        //        return ($return_full ? $return : -1);
        //        //    } elseif ($adx == 1 && $stoch > 0 && $bullish) {
        //      }
        //      elseif ($adx == 1 && $bullish) {
        //        $return['side'] = 'long';
        //        $return['strategy'] = 'stoch_adx';
        //
        //        return ($return_full ? $return : 1);
        //      }
        //    }
    }

    public function phuongbstoch($data = null, $matype1 = TRADER_MA_TYPE_SMA, $matype2 = TRADER_MA_TYPE_SMA, &$text = '')
    {
        if (empty($data['high'])) {
            return 0;
        }
        #$prev_close = $data['close'][count($data['close']) - 2]; // prior close
        #$current = $data['close'][count($data['close']) - 1];    // we assume this is current

        #high,low,close, fastk_period, slowk_period, slowk_matype, slowd_period, slowd_matype
        $stoch = trader_stoch($data['high'], $data['low'], $data['close'], 13, 3, $matype1, 3, $matype2);
        $slowk = $stoch[0];
        $slowd = $stoch[1];

        $slowk = array_pop($slowk); #$slowk[count($slowk) - 1];
        $slowd = array_pop($slowd); #$slowd[count($slowd) - 1];

        $text .= ' K: ' . $slowk;
        $text .= ' D: ' . $slowd;

        #echo "\n(SLOWK: $slowk SLOWD: $slowd)";
        # If either the slowk or slowd are less than 10, the pair is
        # 'oversold,' a long position is opened
        if ($slowk < 10 || $slowd < 10) {
            return 1;
            # If either the slowk or slowd are larger than 90, the pair is
            # 'overbought' and the position is closed.
        } elseif ($slowk > 80 || $slowd > 80) {
            return -1;
        } else {
            return 0;
        }
    }

    public function phuongb_atr($pair, $data, $return_full = false, &$text = '')
    {
        $indicators = new Indicators();
        return $indicators->roc($pair, $data);
    }
}
