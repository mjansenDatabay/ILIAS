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

namespace ILIAS\Tests\Authentication\Infrastructure;

use ILIAS\Authentication\Infrastructure\AuthSession;
use ILIAS\Authentication\Infrastructure\SessionAuthenticatedUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SessionAuthenticatedUserTest extends TestCase
{
    private \ilAuthSession&MockObject $auth_session;

    protected function setUp(): void
    {
        $this->auth_session = $this->createMock(\ilAuthSession::class);
    }

    public function testReturnsAuthenticatedUserIdForFullyAuthenticatedUser(): void
    {
        $this->auth_session->method('isFullyAuthenticated')->willReturn(true);
        $this->auth_session->method('getUserId')->willReturn(42);

        $authenticated_user = new SessionAuthenticatedUser($this->authSessionConnection());

        $result = $authenticated_user->id();

        self::assertTrue($result->isOK());
        self::assertSame(42, $result->value());
    }

    public function testReturnsErrorForAnonymousSession(): void
    {
        $this->auth_session->method('isFullyAuthenticated')->willReturn(false);
        $this->auth_session->method('isAnonymouslyAuthenticated')->willReturn(true);

        $authenticated_user = new SessionAuthenticatedUser($this->authSessionConnection());

        $result = $authenticated_user->id();

        self::assertTrue($result->isError());
        self::assertSame(
            'Anonymous session actor is not an authenticated user.',
            $result->error()
        );
    }

    public function testReturnsErrorWhenSessionIsNotAuthenticated(): void
    {
        $this->auth_session->method('isFullyAuthenticated')->willReturn(false);
        $this->auth_session->method('isAnonymouslyAuthenticated')->willReturn(false);

        $authenticated_user = new SessionAuthenticatedUser($this->authSessionConnection());

        $result = $authenticated_user->id();

        self::assertTrue($result->isError());
        self::assertSame('No authenticated user in session.', $result->error());
    }

    public function testResolvesAuthSessionOnlyWhenIdIsRequested(): void
    {
        $connection = $this->createMock(AuthSession::class);
        $connection->expects(self::never())->method('get');

        new SessionAuthenticatedUser($connection);
    }

    private function authSessionConnection(): AuthSession
    {
        return new readonly class ($this->auth_session) implements AuthSession {
            public function __construct(private \ilAuthSession $auth_session)
            {
            }

            public function get(): \ilAuthSession
            {
                return $this->auth_session;
            }
        };
    }
}
