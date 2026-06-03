<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Tests\Authentication\KeyValueStorage;

use ILIAS\Authentication\Domain\AuthenticatedUser;
use ILIAS\Authentication\KeyValueStorage\SessionAuthenticatedSubjectResolver;
use ILIAS\Data\Result;
use PHPUnit\Framework\TestCase;

class SessionAuthenticatedSubjectResolverTest extends TestCase
{
    public function testReturnsNamedSubjectForAuthenticatedUser(): void
    {
        $user = $this->createMock(AuthenticatedUser::class);
        $user->method('id')->willReturn(new Result\Ok(42));

        $subject = new SessionAuthenticatedSubjectResolver($user)->subject();

        self::assertTrue($subject->isNamed());
        self::assertSame('u42', $subject->id()->storageSegment());
    }

    public function testReturnsAnonymousSubjectWhenUserIsNotAuthenticated(): void
    {
        $user = $this->createMock(AuthenticatedUser::class);
        $user->method('id')->willReturn(new Result\Error('anonymous'));

        self::assertTrue(new SessionAuthenticatedSubjectResolver($user)->subject()->isAnonymous());
    }

    public function testSupportsPersistentStorageForAuthenticatedUser(): void
    {
        $user = $this->createMock(AuthenticatedUser::class);
        $user->method('id')->willReturn(new Result\Ok(42));

        self::assertTrue(new SessionAuthenticatedSubjectResolver($user)->supportsPersistentStorage());
    }

    public function testDoesNotSupportPersistentStorageForAnonymousUser(): void
    {
        $user = $this->createMock(AuthenticatedUser::class);
        $user->method('id')->willReturn(new Result\Error('anonymous'));

        self::assertFalse(new SessionAuthenticatedSubjectResolver($user)->supportsPersistentStorage());
    }
}
