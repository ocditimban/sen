<?php
namespace ph\sen\Doctrine;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use ph\sen\Entity\User;
use ph\sen\Services\EntityServiceTrait;

class UserManager
{
    const BLOCK = 0;
    const ACTIVE = 1;
    const DISABLE = -1;

    /**
     * @var ObjectManager
     */
    protected $objectManager;
    protected $userRepo;

    use EntityServiceTrait;

    /**
     * Constructor.
     *
     * @param ObjectManager            $om
     */
    public function __construct(ObjectManager $om)
    {
        $this->objectManager = $om;
        $this->userRepo = $this->objectManager->getRepository(User::class);
    }

    public function findUserByUserName($userName)
    {
        return $this->userRepo->findOneBy(['user_name' => $userName]);
    }

    public function findUserById($id) {
        return $this->userRepo->find($id);
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

    public function activeUserById($userId)
    {
        if (!$user = $this->findUserById($userId)) {
            return false;
        }

        $user->setStatus(self::ACTIVE);
        $this->updateEntity($user);
        return true;
    }
}