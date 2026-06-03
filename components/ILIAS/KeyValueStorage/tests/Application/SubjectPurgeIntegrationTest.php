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

namespace ILIAS\Tests\KeyValueStorage\Application;

use ILIAS\KeyValueStorage\Application\SubjectPurge;
use ILIAS\KeyValueStorage\Domain\Subject\SubjectId;
use ILIAS\KeyValueStorage\Infrastructure\SubjectPurgeAcrossPorts;
use ILIAS\KeyValueStorage\Port\PersistentStoragePort;
use ILIAS\KeyValueStorage\Port\SessionStoragePort;
use ILIAS\KeyValueStorage\Domain\StorageNamespace;
use ILIAS\KeyValueStorage\Domain\Subject\Subject;
use PHPUnit\Framework\TestCase;

class SubjectPurgeIntegrationTest extends TestCase
{
    public function testSubjectPurgeServiceSupportsSingleAndMassPurge(): void
    {
        $session = new RecordingPurgeStoragePort();
        $persistent = new RecordingPurgeStoragePort();
        /** @var SubjectPurge $subject_purge */
        $subject_purge = new SubjectPurgeAcrossPorts($session, $persistent);

        $subject_purge->purge(new SubjectId('u42'));

        self::assertSame([['u42']], $session->purged_subject_segments);
        self::assertSame([['u42']], $persistent->purged_subject_segments);

        $subject_purge->purgeMany([new SubjectId('u7'), new SubjectId('u8')]);

        self::assertSame([['u42'], ['u7', 'u8']], $session->purged_subject_segments);
        self::assertSame([['u42'], ['u7', 'u8']], $persistent->purged_subject_segments);
    }

    public function testSubjectPurgeServiceIsRegisteredUnderInterfaceClassName(): void
    {
        $service = new SubjectPurgeAcrossPorts(
            new RecordingPurgeStoragePort(),
            new RecordingPurgeStoragePort()
        );

        self::assertInstanceOf(SubjectPurge::class, $service);
    }
}

final class RecordingPurgeStoragePort implements SessionStoragePort, PersistentStoragePort
{
    /** @var list<list<string>> */
    public array $purged_subject_segments = [];

    public function has(StorageNamespace $namespace, string $key, Subject $subject): bool
    {
        return false;
    }

    public function read(StorageNamespace $namespace, string $key, Subject $subject): ?string
    {
        return null;
    }

    public function write(StorageNamespace $namespace, string $key, string $value, Subject $subject): void
    {
    }

    public function remove(StorageNamespace $namespace, string $key, Subject $subject): void
    {
    }

    public function clearNamespace(StorageNamespace $namespace, Subject $subject): void
    {
    }

    public function purgeSubject(SubjectId $subject): void
    {
        $this->purgeSubjects([$subject]);
    }

    public function purgeSubjects(array $subjects): void
    {
        $this->purged_subject_segments[] = \array_map(
            static fn(SubjectId $subject): string => $subject->storageSegment(),
            $subjects
        );
    }
}
