<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class UserSubscriber implements EventSubscriber
{
    private string $projectDir;

    private string $uploadedAvatarsFolder;

    private string $defaultAvatarFile;

    public function __construct(
        string $projectDir,
        string $uploadedAvatarsFolder,
        string $defaultAvatarFile
    )
    {
        $this->projectDir = $projectDir;
        $this->uploadedAvatarsFolder = $uploadedAvatarsFolder;
        $this->defaultAvatarFile = $defaultAvatarFile;
    }

    /**
     * @param LifecycleEventArgs $args
     * @return void
     *
     * Function to set a default avatar for an user registered
     * without an avatar just before we persist it in order
     * to save it in the database
     */
    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        // We check if the entity is an instance of User
        if(!$entity instanceof User) {
            return;
        }

        // We check if no avatar was upload
        if(!$entity->getAvatar()) {
            // We set the default avatar
            $entity->setAvatar($this->projectDir.$this->uploadedAvatarsFolder.'/'.$this->defaultAvatarFile);
        }
    }

    public function getSubscribedEvents(): array
    {
        return[
            'prePersist',
        ];
    }
}