<?php

namespace App\Service;

use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadFileService
{

    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param $fileFolder
     * @return string[]
     *
     * Method to upload a file and store it to the folder dedicated to the
     * uploaded photos
     */
    public function uploadFile(UploadedFile $uploadedFile, $fileFolder)
    {
        /** @var UploadedFile $uploadedFile  */
        // We configure the fact that the photos will be store in the folder /public/uploads/photos
        // represented by the value $destination
        $destination = $this->projectDir.$fileFolder;

        // We handle any false extension problem and set a unique name to the photo upload
        // which is $newFilename
        $originalFilename =  Urlizer::urlize(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME));
        $newFilename = $originalFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();

        // Then we send the uploadedFile in the folder /public/upload/photos
        // represented by the value $destination
        $uploadedFile->move($destination, $newFilename);

        return[
            'name' => $newFilename,
            'url' => $destination.$newFilename,
        ];

    }

}