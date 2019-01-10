<?php
namespace ph\sen\Doctrine;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use ph\sen\Entity\User;

class ActivityManager
{
    public function findActivityByUuid($uuid, $userId)
    {
        return $this->activityRepo->findOneBy(['uuid' => $uuid, 'uid' => $userId, 'outcome' => 'pending']);
    }

    public function findActivityByOrderId($orderId): ?Activity
    {
        return $this->activityRepo->findOneBy(['tradeId' => $orderId]);
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
        $this->entityManage->persist($activity);
        $this->entityManage->flush();
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

    public function getActivityByUserId($text, $userId)
    {
        $uuid = ltrim($text, "/");
        $longUuid = $this->expandUuid($uuid);

        if ($activity = $this->findActivityByUuid($longUuid, $userId)) {
            return $activity;
        }
    }

    public function updateStatus($activity) {
        $activity->setOutcome('finished');
        $this->entityManage->persist($activity);
        $this->entityManage->flush();
    }

}