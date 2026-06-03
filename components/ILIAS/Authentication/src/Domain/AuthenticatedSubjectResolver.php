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

namespace ILIAS\Authentication\Domain;

use ILIAS\KeyValueStorage\Domain\Subject\SubjectResolver;

/**
 * Resolves the current fully authenticated ILIAS user to a KeyValueStorage subject.
 *
 * Anonymous or missing authentication yields an anonymous subject.
 */
interface AuthenticatedSubjectResolver extends SubjectResolver
{
    /**
     * Whether the resolved actor may use persistent storage backends.
     */
    public function supportsPersistentStorage(): bool;
}
