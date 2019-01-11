<?php
namespace ph\sen\Doctrine;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use ph\sen\Entity\Profit;
use ph\sen\Entity\User;

class ProfitManager
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;
    protected $profitRepo;

    /**
     * Constructor.
     *
     * @param ObjectManager            $om
     */
    public function __construct(ObjectManager $om)
    {
        $this->objectManager = $om;
        $this->profitRepo = $this->objectManager->getRepository(Profit::class);
    }

    public function findProfitByCreatedDate($userId, $createdDate, $exchange = 'bn'): ?Profit
    {
        return $this->profitRepo->findOneBy(['uid' => $userId, 'exchange' => $exchange, 'created_date' => $createdDate]);
    }

    public function insertProfit($uid, $exchange, $createdDate, $percent, $data)
    {
        $profit = new Profit();
        $profit->setUid($uid);
        $profit->setExchange($exchange);
        $profit->setCreatedDate($createdDate);
        $profit->setPercent($percent);
        $profit->setData($data);
        $this->objectManager->persist($profit);
        $this->objectManager->flush();
    }

    public function updateProfit($profit)
    {
        $this->objectManager->persist($profit);
        $this->objectManager->flush();
    }

    public function calculatorProfit($uid, $time, $percent, $money, $exchange = 'bn')
    {
        $profit = $this->findProfitByCreatedDate($uid, $time, $exchange);
        $data = json_encode(['money' => $money]);
        if (!$profit) {
            $this->insertProfit($uid, $exchange, $time, $percent, $data);
        }
        else {
            $profit->setPercent($profit->getPercent() + $percent);
            $profit->setData($data);
            $this->updateProfit($profit);
        }
    }
}