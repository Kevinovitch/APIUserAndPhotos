<?php

namespace App\Service;

use App\Entity\Photo;

class PhotoService
{
    public function createPhotoWhileCreatingUser($filename, $url): Photo
    {
        $photo = new Photo();
        $photo->setName($filename);
        $photo->setUrl($url);

        return $photo;

    }
}