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

use ILIAS\Authentication\KeyValueStorage\DicAuthenticatedSubjectPurge;
use ILIAS\Authentication\KeyValueStorage\UserDeletedSubjectPurgeHandler;

/**
 * Legacy application event listener for the Authentication component.
 */
class ilAuthenticationAppEventListener implements ilAppEventListener
{
    public static function handleEvent(string $component, string $event, array $parameter): void
    {
        if (!self::isUserDeletedEvent($component, $event)) {
            return;
        }

        $user_id = (int) ($parameter['usr_id'] ?? 0);
        if ($user_id <= 0) {
            return;
        }

        global $DIC;

        if (!isset($DIC[\ILIAS\KeyValueStorage\Application\SubjectPurge::class])) {
            return;
        }

        self::handler(new DicAuthenticatedSubjectPurge())->handle($user_id);
    }

    private static function isUserDeletedEvent(string $component, string $event): bool
    {
        if ($event !== 'deleteUser') {
            return false;
        }

        return $component === 'Services/User' || $component === 'components/ILIAS/User';
    }

    private static function handler(DicAuthenticatedSubjectPurge $subject_purge): UserDeletedSubjectPurgeHandler
    {
        return new UserDeletedSubjectPurgeHandler($subject_purge->get());
    }
}
