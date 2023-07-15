<?php

namespace App\Service;


use App\Entity\Photo;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Function to get all the active users created during the last
     * amount of time corresponding to $datetime
     * @param $date
     * @return array
     */
    public function getActiveUsersCreatedAfter($date): array
    {
        return $this->entityManager->getRepository(User::class)->findActiveUsersCreatedAfter($date);
    }

    /**
     * Function to create a user from the payload of a POST request
     * to validate its datas
     * @param array $postDatas
     * @return User
     */
    public function createUserFromPostRequestForValidation(array $postDatas): User
    {
        $user = new User();
        foreach($postDatas as $postData) {
            foreach ($postData as $key => $value) {
                $setter = 'set' . ucfirst($key);
                $user->$setter($value);
            }

        }

        return $user;
    }

    /**
     * @param User $user
     * @param $objectPhotos
     * @param $avatar
     * @return void
     *
     * Function to save a user, its photos and its avatar in the database
     */
    public function saveUserAndPhotosCreated(User $user, $objectPhotos, $avatar = null) :void
    {
        if($avatar)
        {
            $user->setAvatar($avatar['url']);
        }
        foreach($objectPhotos as $objectPhoto)
        {
            /** @var Photo $objectPhoto  */
            $objectPhoto->setUser($user);
            $user->addPhoto($objectPhoto);
            $this->entityManager->persist($objectPhoto);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * @param $email
     * @param $password
     * @return User|mixed|object|null
     *
     * Methode to get a User by the combination of an email and a password
     */
    public function getUserByEmailAndPassword($email, $password)
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email, 'password' => $password]);
    }

    /**
     * @param User $user
     * @return void
     *
     * Method to authenticate a User by giving it the role
     * 'IS_AUTHENTICATED_FULLY'
     */
    public function fullyAuthenticateUser(User $user): void
    {
        $user->setRoles(['IS_AUTHENTICATED_FULLY']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }



}