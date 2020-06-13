<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\Action\User;

use App\Api\Action\User\Register;
use App\Entity\User;
use App\Exception\User\UserAlreadyExistException;
use App\Repository\UserRepository;
use App\Service\Password\EncoderService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RegisterTest extends TestCase
{
    /** @var ObjectProphecy|UserRepository */
    private $userRepositoryProphecy;

    private UserRepository $userRepository;

    /** @var ObjectProphecy|JWTTokenManagerInterface */
    private $JWTTokenManagerProphecy;

    private JWTTokenManagerInterface $JWTTokenManager;

    /** @var ObjectProphecy|EncoderService */
    private $encoderServiceProphecy;

    private EncoderService $encoderService;

    private Register $action;

    public function setUp(): void
    {
        $this->userRepositoryProphecy = $this->prophesize(UserRepository::class);
        $this->userRepository = $this->userRepositoryProphecy->reveal();

        $this->JWTTokenManagerProphecy = $this->prophesize(JWTTokenManagerInterface::class);
        $this->JWTTokenManager = $this->JWTTokenManagerProphecy->reveal();

        $this->encoderServiceProphecy = $this->prophesize(EncoderService::class);
        $this->encoderService = $this->encoderServiceProphecy->reveal();

        $this->action = new Register($this->userRepository, $this->JWTTokenManager, $this->encoderService);
    }

    /**
     * @throws \Exception
     */
    public function testCreateUser(): void
    {
        $payload = [
            'name' => 'Username',
            'email' => 'username@api.com',
            'password' => 'random_password',
        ];

        $request = new Request([], [], [], [], [], [], \json_encode($payload));

        $this->userRepositoryProphecy->findOneByEmail($payload['email'])->willReturn(null);

        $this->encoderServiceProphecy->generateEncodedPasswordForUser(
            Argument::that(
                function (User $user): bool {
                    return true;
                }
            ),
            Argument::type('string')
        )->shouldBeCalledOnce();

        $this->userRepositoryProphecy->save(
            Argument::that(
                function (User $user): bool {
                    return true;
                }
            )
        )->shouldBeCalledOnce();

        $this->JWTTokenManagerProphecy->create(
            Argument::that(
                function (User $user): bool {
                    return true;
                }
            )
        )->shouldBeCalledOnce();

        $response = $this->action->__invoke($request);

        $this->assertEquals(JsonResponse::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testCreateUserForExistingEmail(): void
    {
        $payload = [
            'name' => 'Username',
            'email' => 'username@api.com',
            'password' => 'random_password',
        ];

        $request = new Request([], [], [], [], [], [], \json_encode($payload));

        $user = new User($payload['name'], $payload['email']);

        $this->userRepositoryProphecy->findOneByEmail($payload['email'])->willReturn($user);

        $this->expectException(UserAlreadyExistException::class);

        $this->action->__invoke($request);
    }
}
