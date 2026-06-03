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

namespace ILIAS\KeyValueStorage\Infrastructure;

use ILIAS\KeyValueStorage\Application\SubjectPurge;
use ILIAS\KeyValueStorage\Domain\Subject\SubjectId;
use ILIAS\KeyValueStorage\Port\PersistentStoragePort;
use ILIAS\KeyValueStorage\Port\SessionStoragePort;

/**
 * Delegates subject purge to all storage ports.
 */
final readonly class SubjectPurgeAcrossPorts implements SubjectPurge
{
    public function __construct(
        private SessionStoragePort $session_storage_port,
        private PersistentStoragePort $persistent_storage_port
    ) {
    }

    public function purge(SubjectId $subject): void
    {
        $this->purgeMany([$subject]);
    }

    public function purgeMany(array $subjects): void
    {
        if ($subjects === []) {
            return;
        }

        $this->session_storage_port->purgeSubjects($subjects);
        $this->persistent_storage_port->purgeSubjects($subjects);
    }
}
