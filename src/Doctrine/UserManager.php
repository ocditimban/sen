<?php
namespace ph\sen\Doctrine;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use App\Entity\User;

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

    /**
     * {@inheritdoc}
     */
    public function deleteUser(User $user)
    {
        $this->objectManager->remove($user);
        $this->objectManager->flush();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        if (false !== strpos($this->class, ':')) {
            $metadata = $this->objectManager->getClassMetadata($this->class);
            $this->class = $metadata->getName();
        }
        return $this->class;
    }
    /**
     * {@inheritdoc}
     */
    public function findUserBy(array $criteria)
    {
        return $this->getRepository()->findOneBy($criteria);
    }
    /**
     * {@inheritdoc}
     */
    public function findUsers()
    {
        return $this->getRepository()->findAll();
    }
    /**
     * {@inheritdoc}
     */
    public function reloadUser(UserInterface $user)
    {
        $this->objectManager->refresh($user);
    }
    /**
     * {@inheritdoc}
     */
    public function updateUser(UserInterface $user, $andFlush = true)
    {
        $this->updateCanonicalFields($user);
        $this->updatePassword($user);
        $this->objectManager->persist($user);
        if ($andFlush) {
            $this->objectManager->flush();
        }
    }
    /**
     * @return ObjectRepository
     */
    protected function getRepository()
    {
        return $this->objectManager->getRepository($this->getClass());
    }
}