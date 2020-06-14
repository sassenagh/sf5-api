<?php

declare(strict_types=1);

namespace App\Security\Authorization\Voter;

use App\Entity\Group;
use App\Entity\User;
use App\Security\Role;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class GroupVoter extends BaseVoter
{
    private const GROUP_READ = 'GROUP_READ';
    private const GROUP_CREATE = 'GROUP_CREATE';
    private const GROUP_UPDATE = 'GROUP_UPDATE';
    private const GROUP_DELETE = 'GROUP_DELETE';

    protected function supports(string $attribute, $subject)
    {
        return \in_array($attribute, $this->getSupportedAttributes(), true);
    }

    /**
     * @param Group|null $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {
        /** @var User $tokenUser */
        $tokenUser = $token->getUser();

        if (self::GROUP_READ === $attribute) {
            if (null === $subject) {
                return $this->security->isGranted(Role::ROLE_ADMIN);
            }

            return $this->security->isGranted(Role::ROLE_ADMIN)
                || $this->groupRepository->userIsMember($subject, $tokenUser);
        }

        if (self::GROUP_CREATE === $attribute) {
            return true;
        }

        if (self::GROUP_UPDATE === $attribute) {
            return $this->security->isGranted(Role::ROLE_ADMIN)
                || $this->groupRepository->userIsMember($subject, $tokenUser);
        }

        if (self::GROUP_DELETE === $attribute) {
            return $this->security->isGranted(Role::ROLE_ADMIN)
                || $subject->isOwnedBy($tokenUser);
        }

        return false;
    }

    private function getSupportedAttributes(): array
    {
        return [
            self::GROUP_READ,
            self::GROUP_CREATE,
            self::GROUP_UPDATE,
            self::GROUP_DELETE,
        ];
    }
}
