<?php

declare(strict_types=1);

namespace App\Api\Action\User;

use App\Api\Action\RequestTransformer;
use App\Entity\User;
use App\Exception\User\UserAlreadyExistException;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class Register
{
    private UserRepository $userRepository;

    private JWTTokenManagerInterface $JWTTokenManager;

    private EncoderFactoryInterface $encoderFactory;

    public function __construct(UserRepository $userRepository, JWTTokenManagerInterface $JWTTokenManager, EncoderFactoryInterface $encoderFactory)
    {
        $this->userRepository = $userRepository;
        $this->JWTTokenManager = $JWTTokenManager;
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * @Route("/users/register", methods={"POST"})
     *
     * @throws \Exception
     */
    public function __invoke(Request $request): JsonResponse
    {
        $name = RequestTransformer::getRequiredField($request, 'name');
        $email = RequestTransformer::getRequiredField($request, 'email');
        $password = RequestTransformer::getRequiredField($request, 'password');

        $existingUser = $this->userRepository->findOneByEmail($email);
        if (null !== $existingUser) {
            throw UserAlreadyExistException::fromUserEmail($email);
        }

        $user = new User($name, $email);

        $encoder = $this->encoderFactory->getEncoder($user);

        $user->setPassword($encoder->encodePassword($password, null));

        $this->userRepository->save($user);

        $jwt = $this->JWTTokenManager->create($user);

        return new JsonResponse(['token' => $jwt]);
    }
}
