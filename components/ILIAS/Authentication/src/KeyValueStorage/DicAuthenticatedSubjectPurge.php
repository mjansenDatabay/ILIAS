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

/**
 * Resolves {@see AuthenticatedSubjectPurge} from the global DIC during legacy bootstrap.
 */
final readonly class DicAuthenticatedSubjectPurge
{
    public function get(): AuthenticatedSubjectPurge
    {
        global $DIC;

        /** @var \ILIAS\KeyValueStorage\Application\SubjectPurge $subject_purge */
        $subject_purge = $DIC[\ILIAS\KeyValueStorage\Application\SubjectPurge::class];

        return new AuthenticatedSubjectPurge($subject_purge);
    }
}
