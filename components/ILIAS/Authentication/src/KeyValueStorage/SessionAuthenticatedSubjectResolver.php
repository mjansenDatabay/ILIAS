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

namespace ILIAS\Authentication\KeyValueStorage;

use ILIAS\Authentication\Domain\AuthenticatedSubjectResolver;
use ILIAS\Authentication\Domain\AuthenticatedUser;
use ILIAS\Authentication\Domain\AuthenticatedUserSubjectId;
use ILIAS\KeyValueStorage\Domain\Subject\Subject;

/**
 * Session-backed implementation of {@see AuthenticatedSubjectResolver}.
 */
final readonly class SessionAuthenticatedSubjectResolver implements AuthenticatedSubjectResolver
{
    public function __construct(private AuthenticatedUser $authenticated_user)
    {
    }

    public function subject(): Subject
    {
        $user_id = $this->authenticated_user->id();

        if ($user_id->isError()) {
            return Subject::anonymous();
        }

        return Subject::named(AuthenticatedUserSubjectId::fromUserId($user_id->value()));
    }

    public function supportsPersistentStorage(): bool
    {
        return !$this->authenticated_user->id()->isError();
    }
}
