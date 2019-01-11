<?php

namespace ph\sen\Services;

use App\Entity\Activity;
use App\Entity\Profit;
use App\Entity\User;
use App\Exchange\BinanceExchange;
use ph\sen\ErrorMessage;
use App\Repository\ActivityRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Exception\RequestException;
use Keiko\Uuid\Shortener\Dictionary;
use Keiko\Uuid\Shortener\Number\BigInt\Converter;
use Keiko\Uuid\Shortener\Shortener;
use ph\sen\Doctrine\ActivityManager;
use ph\sen\Doctrine\ProfitManager;
use ph\sen\Doctrine\UserManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Exception;
use stdClass;


class HelperService
{
    private $text;
    private $messages;
    CONST BINANCE_SIGN = 'bn';
    CONST BT_SIGN      = 'bt';

    const BLOCK = 0;
    const ACTIVE = 1;
    const DISABLE = -1;

    public $objectManager;
    public $activity;
    public $user;
    public $profit;
    private $templateService;
    
    use EntityServiceTrait;
    use BinanceServiceTrait;

    public function __construct(ObjectManager $objectManager, TemplateService $templateService, ActivityManager $activityManager, UserManager $userManager, ProfitManager $profitManager)
    {
        $this->messages = new ErrorMessage();
        $this->objectManager = $objectManager;
        $this->activity = $activityManager;
        $this->user = $userManager;
        $this->profit = $profitManager;
        $this->templateService = $templateService;
    }

    public function validateExchange($sign)
    {
        if (strtolower($sign) == self::BINANCE_SIGN) {
            return true;
        }
        //    if (strtolower($sign) == self::BT_SIGN) {
        //      return true;
        //    }

        $this->messages->log('The exchange not found');

        return false;
    }

    public function getMessage()
    {
        return $this->messages->getMessages();
    }

    /**
     * @param $sign
     * @param $userId
     * @return BinanceExchange|null
     */
    public function getExchange($userId, $sign = 'bn'): ?BinanceExchange
    {
        if (strtolower($sign) == self::BINANCE_SIGN) {
            $user = $this->findUserById($userId);
            return new BinanceExchange($user->getApiKey(), $user->getSecretKey());
        }

        return null;
    }

    public function getBalanceInfo($uid)
    {
        $exchange = $this->getExchange('bn', $uid);
        $balances = $exchange->getBalance();

        return $this->templateService->renderBalances($balances);
    }

    public function processUserCommand($text, $uid)
    {
        if ($args = explode(" ", $text)) {
            switch ($args[0]) {
                case '/price':
                    return $this->getPriceInfo($args[1], $uid);

                case '/bl':
                    return $this->getBalanceInfo($uid);
            }
        }
    }

    public function formatSymbol($symbol)
    {
        $noBtc = (strpos($symbol, 'btc') == false);
        $noEth = (strpos($symbol, 'eth') == false);
        $noUsdt = (strpos($symbol, 'usdt') == false);

        if ($noBtc && $noEth && $noUsdt) {
            $symbol .= 'btc';
        }

        return strtoupper($symbol);
    }

    public function getPriceInfo($symbol, $uid)
    {
        $exchange = $this->getExchange('bn', $uid);
        $symbol = $this->formatSymbol($symbol);
        $symbolInfo = $exchange->getPrevDay($symbol);

        return $this->templateService->renderPriceSymbol(
            $symbolInfo['commandName'],
            $symbolInfo['quoteAsset'],
            $symbolInfo['lastPrice'],
            $symbolInfo['bidPrice'],
            $symbolInfo['askPrice'],
            $symbolInfo['lowPrice'],
            $symbolInfo['highPrice'],
            round($symbolInfo['priceChangePercent'], 2),
            round($symbolInfo['quoteVolume'], 2)
        );
    }

    public function detach($text = null, $uid)
    {
        $text = ($text) ?? $this->text;
        if ($activity = $this->getActivityByUserId($text, $uid)) {
            $this->updateStatus($activity);

            return $this->process($activity, $uid);
        }

        if ($result = $this->processUserCommand($text, $uid)) {
            return $result;
        }

        return $this->processCommand($text, $uid);
    }

    public function processCommand($text, $uid)
    {
        $args = explode(' ', $text);
        $commandName = array_shift($args);
        $commandName .= 'Command';

        $sign = substr($commandName, 0, 2);
        $commandName = ucfirst(substr($commandName, 2));
        if (!$this->validateExchange($sign)) {
            return $this->messages->getMessages();
        }

        $className = "App\\Command\\" . $commandName;
        $command = new $className();

        if (!$command->validate($args)) {
            return $command->message->getMessages();
        }

        $command->addOjbect($args);
        $exchange = $this->getExchange($sign, $uid);

        $uuid = Uuid::uuid4()->toString();
        $this->insertActivity($uuid, $uid, $className, $sign, 'pending', $args);
        $confirmInfo = $command->confirmInfo($exchange);
        $confirmInfo['uuid'] = $this->reduceUuid($uuid);

        return $this->templateService->renderConfirmTemplete(
            $confirmInfo['commandName'],
            $confirmInfo['exchange'],
            $confirmInfo['symbol'],
            $confirmInfo['quantity'],
            $confirmInfo['price'],
            $confirmInfo['diff'],
            $confirmInfo['currentPrice'],
            $confirmInfo['havingAsset'],
            $confirmInfo['targetAsset'],
            $confirmInfo['total'],
            $confirmInfo['uuid'],
            $confirmInfo['stop'],
            $confirmInfo['limit']
        );
    }
}
