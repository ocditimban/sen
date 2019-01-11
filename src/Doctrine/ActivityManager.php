<?php
namespace ph\sen\Doctrine;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use ph\sen\Entity\Activity;
use ph\sen\Entity\User;
use ph\sen\Services\EntityServiceTrait;

class ActivityManager
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;
    protected $activityRepo;

    use EntityServiceTrait;

    /**
     * Constructor.
     *
     * @param ObjectManager            $om
     */
    public function __construct(ObjectManager $om)
    {
        $this->objectManager = $om;
        $this->activityRepo = $this->objectManager->getRepository(Activity::class);
    }

    public function findActivityByUuid($uuid, $userId)
    {
        return $this->activityRepo->findOneBy(['uuid' => $uuid, 'uid' => $userId, 'outcome' => 'pending']);
    }

    public function findActivityByOrderId($orderId): ?Activity
    {
        return $this->activityRepo->findOneBy(['tradeId' => $orderId]);
    }

    public function findActivityById($id): ?Activity
    {
        return $this->activityRepo->find($id);
    }

    public function insertActivity($uuid, $uid, $class, $exchange, $outcome, $data)
    {
        $activity = new Activity();
        $activity->setData(json_encode($data));
        $activity->setUid($uid);
        $activity->setClass($class);
        $activity->setExchange($exchange);
        $activity->setUuid($uuid);
        $activity->setOutcome($outcome);

        $this->updateEntity($activity);
    }

    public function getActivityByUserId($text, $userId)
    {
        $uuid = ltrim($text, "/");
        $longUuid = $this->expandUuid($uuid);

        if ($activity = $this->findActivityByUuid($longUuid, $userId)) {
            return $activity;
        }
    }

    public function updateStatus($activity, $outcome = 'finished') {
        $activity->setOutcome($outcome);
        $this->updateEntity($activity);
    }

    public function findActivityByOutcome($userId, $outcome): ?Activity
    {
        return $this->activityRepo->findOneBy(['uid' => $userId, 'outcome' => $outcome]);
    }

    public function findLatestActivity()
    {
        return $this->activityRepo->findOneBy([], ['id' => 'DESC']);
    }

    public function process(Activity $activity, $userId)
    {
        $className = $activity->getClass();
        $command = new $className();
        $command->addOjbect(json_decode($activity->getData(), true));
        $exchange = $this->getExchange($activity->getExchange(), $userId);
        $orderId = $command->process($exchange);
        $activity->setTradeId($orderId);
        $this->updateEntity($activity);

        return $orderId;
    }

    public function expandUuid($shorterUuid) {
        try {
            $shorter = new Shortener(
                Dictionary::createUnmistakable(),
                new Converter()
            );
            $shorter->expand($shorterUuid);
        }
        catch (Exception $e) {
            return false;
        }
        return $shorter->expand($shorterUuid);
    }

    public function reduceUuid($longUuid) {
        $shorter = new Shortener(
            Dictionary::createUnmistakable(),
            new Converter()
        );

        return $shorter->reduce($longUuid);
    }
}