<?php
namespace ph\sen\Doctrine;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use ph\sen\Entity\User;

class UserManager
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Constructor.
     *
     * @param ObjectManager            $om
     */
    public function __construct(ObjectManager $om)
    {
        $this->objectManager = $om;
    }

    public function findUserByUserName($userName)
    {
        return $this->getRepository->findOneBy(['user_name' => $userName]);
    }

    public function findUserById($id) {
        return $this->getRepository->find($id);
    }

    public function isDisableUser($id)
    {
        $user = $this->findUserById($id);
        return (self::DISABLE == $user->getStatus());
    }

    public function userGetData($id)
    {
        $user = $this->findUserById($id);
        return json_decode($user->getData(), true);
    }

    public function isActiveUser($id)
    {
        $user = $this->findUserById($id);
        return (self::ACTIVE == $user->getStatus());
    }

    public function blockUserById($userId)
    {
        if (!$user = $this->findUserById($userId)) {
            return false;
        }

        $user->setStatus(self::BLOCK);
        $this->updateEntity($user);
        return true;
    }

    /**
     * @return ObjectRepository
     */
    protected function getRepository()
    {
        return $this->objectManager->getRepository(User::class);
    }
}