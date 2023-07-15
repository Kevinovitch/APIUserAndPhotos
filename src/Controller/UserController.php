<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserLoginType;
use App\Form\UserType;
use App\Serializer\FormErrorSerializer;
use App\Service\PhotoService;
use App\Service\UploadFileService;
use App\Service\UserService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * API to get some data concerning users
 */
#[Route('/api')]
class UserController extends AbstractController
{
    private UserService $userService;

    private UploadFileService $uploadFileService;

    private PhotoService $photoService;

    private FormErrorSerializer $formErrorSerializer;

    private ValidatorInterface $validator;

    private TokenStorageInterface $tokenStorage;

    private JWTTokenManagerInterface $JWTManager;

    private string $uploadedPhotosFolder;

    private string $uploadedAvatarsFolder;

    public function __construct(
        UserService $userService,
        UploadFileService $uploadFileService,
        PhotoService $photoService,
        FormErrorSerializer $formErrorSerializer,
        ValidatorInterface $validator,
        TokenStorageInterface $tokenStorage,
        JWTTokenManagerInterface $JWTManager,
        string $uploadedPhotosFolder,
        string $uploadedAvatarsFolder
    )
    {
        $this->userService = $userService;
        $this->uploadFileService = $uploadFileService;
        $this->photoService = $photoService;
        $this->formErrorSerializer = $formErrorSerializer;
        $this->validator = $validator;
        $this->tokenStorage = $tokenStorage;
        $this->JWTManager = $JWTManager;
        $this->uploadedPhotosFolder = $uploadedPhotosFolder;
        $this->uploadedAvatarsFolder = $uploadedAvatarsFolder;

    }


    /**
     * Function to register a new user
     */
    #[Route('/users/register', name: 'app_users_register', methods: ['GET', 'POST'])]
    public function new(Request $request)
    {

        // We use the UserType to validate these datas
        $form = $this->createForm(UserType::class, new User());

        // We get the data sent by the client and with them we create a User to validate these datas
        // thanks to the createUserFromPostRequestForValidation() methods and then we submit the form
        $userFromPostDatas = $this->userService->createUserFromPostRequestForValidation((array)$request->getPayload());
        $form->submit($userFromPostDatas);
        // We handle the request to keep the datas submitted for the upload of files
        $form->handleRequest($request);

        // We use the validator to validate the data sent by the client
        $errors = $this->validator->validate($userFromPostDatas);

        if(count($errors) > 0)
        {
            $errorsString = (string)$errors;

            return new JsonResponse(['status' => 'error', 'errors' => $errorsString], Response::HTTP_BAD_REQUEST);
        }
        // If less than 4 photos were selected
        else if(count($request->files->get('photoFiles')) < 4)
        {
            return new JsonResponse(['status' => 'error', 'message' => 'You must upload at least 4 photos'], Response::HTTP_BAD_REQUEST);
        }
        else
        {
            /** @var UploadedFile $photoFiles  */
            $photoFiles = $request->files->get('photoFiles');
            /** @var UploadedFile $avatarFile  */
            $avatarFile = $request->files->get('avatarFile');

            $objectPhotos = array();
            foreach ($photoFiles as $photoFile)
            {
                // We upload each photo thanks to the uploadFile method of the uploadFileService
                $photoFileUploadArray = $this->uploadFileService->uploadFile($photoFile, $this->uploadedPhotosFolder);

                // We create an object corresponding to the uploadedFile $photoFile
                // with the data collected previously which are in the $photoFileUploadArray array
                $objectPhoto = $this->photoService->createPhotoWhileCreatingUser($photoFileUploadArray['name'], $photoFileUploadArray['url']);

                $objectPhotos[] = $objectPhoto;
            }

            // If there is a file sent as an avatar, we upload it
            $avatar =  ($avatarFile) ? $this->uploadFileService->uploadFile($avatarFile, $this->uploadedAvatarsFolder) : null;


            // Then we save the user with its photos and its avatar in the database
            $this->userService->saveUserAndPhotosCreated($userFromPostDatas, $objectPhotos, $avatar);

            // Eventually, we return a 201 response
            return new JsonResponse(['status' => 'ok'], Response::HTTP_CREATED);

        }

    }

    /**
     * Action to make the connection of
     * an existing user with email and password
     */
    #[Route('/users/login', name: 'app_users_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        // We collect the data of the POST request and turn them into
        // an associative array
        $userDatas = json_decode($request->getContent(), true);

        // We use the UserLoginType to validate theses datas
        $form = $this->createForm(UserLoginType::class);
        $form->submit($userDatas);

        // If there are false we return a 400 response
        if (false === $form->isValid()) {

            return new JsonResponse([
                'status' => 'error',
                'errors' => $this->formErrorSerializer->convertFormToArray($form)
            ],
                Response::HTTP_BAD_REQUEST
            );

        }
        // We check if there is a user with the email and the password
        // sent by the client with the method getUserByEmailAndPassword()
        else
        {
            $checkAuthenticateUser = $this->userService->getUserByEmailAndPassword($form->getData()->getEmail(), $form->getData()->getPassword());

            // We sent a 404 response if there is no user
            if(null === $checkAuthenticateUser)
            {
                return new JsonResponse(['status' => 'error'], Response::HTTP_NOT_FOUND);
            }
            else
            {
                // We create the token and we store in the local storage
                $jwtToken = $this->JWTManager->create($checkAuthenticateUser);

                $tokenStorageToken = new UsernamePasswordToken($checkAuthenticateUser, $jwtToken, ['IS_AUTHENTICATED_FULLY']);
                $this->tokenStorage->setToken($tokenStorageToken);


                // Then we return the token

                return new JsonResponse(['token' => $jwtToken], Response::HTTP_OK);

            }

        }
    }

    /**
     * Action to display of the authenticated user
     *
     */
    #[Route('/users/me', name: 'app_users_show', methods: ['GET'])]
    public function showCurrentUser(): JsonResponse
    {

        // We get the token which was stored in the TokenStorage
        // to get the user attached to this token
        $token = $this->tokenStorage->getToken();
        if ($token instanceof TokenInterface) {

            /** @var User $user */
            $user = $token->getUser();
            return new JsonResponse($user, Response::HTTP_OK);

        } else {
            return new JsonResponse(['status' => 'error'], Response::HTTP_FORBIDDEN);
        }


    }

}
