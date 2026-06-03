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

namespace ILIAS\Tests\KeyValueStorage\Infrastructure;

use ILIAS\KeyValueStorage\Domain\Subject\SubjectId;
use ILIAS\KeyValueStorage\Infrastructure\SubjectPurgeAcrossPorts;
use ILIAS\KeyValueStorage\Port\PersistentStoragePort;
use ILIAS\KeyValueStorage\Port\SessionStoragePort;
use PHPUnit\Framework\TestCase;

class SubjectPurgeAcrossPortsTest extends TestCase
{
    public function testPurgesSubjectOnSessionAndPersistentPorts(): void
    {
        $subject = new SubjectId('u42');
        $session = $this->createMock(SessionStoragePort::class);
        $persistent = $this->createMock(PersistentStoragePort::class);

        $session->expects($this->once())->method('purgeSubjects')->with([$subject]);
        $persistent->expects($this->once())->method('purgeSubjects')->with([$subject]);

        new SubjectPurgeAcrossPorts($session, $persistent)->purge($subject);
    }

    public function testPurgesManySubjects(): void
    {
        $subjects = [new SubjectId('u7'), new SubjectId('u8')];
        $session = $this->createMock(SessionStoragePort::class);
        $persistent = $this->createMock(PersistentStoragePort::class);

        $session->expects($this->once())->method('purgeSubjects')->with($subjects);
        $persistent->expects($this->once())->method('purgeSubjects')->with($subjects);

        new SubjectPurgeAcrossPorts($session, $persistent)->purgeMany($subjects);
    }

    public function testIgnoresEmptySubjectList(): void
    {
        $session = $this->createMock(SessionStoragePort::class);
        $persistent = $this->createMock(PersistentStoragePort::class);

        $session->expects($this->never())->method('purgeSubjects');
        $persistent->expects($this->never())->method('purgeSubjects');

        new SubjectPurgeAcrossPorts($session, $persistent)->purgeMany([]);
    }
}
