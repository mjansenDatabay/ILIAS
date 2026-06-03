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

use ILIAS\KeyValueStorage\Domain\Storage;
use ILIAS\KeyValueStorage\Domain\StorageNamespace;
use ILIAS\KeyValueStorage\Application\SubjectStorages;
use ILIAS\KeyValueStorage\Domain\Subject\Subject;
use ILIAS\KeyValueStorage\Domain\Subject\SubjectId;
use ILIAS\KeyValueStorage\Domain\Subject\SubjectResolver;
use ILIAS\KeyValueStorage\Port\SubjectAwareStorages;
use PHPUnit\Framework\TestCase;

class SubjectStoragesTest extends TestCase
{
    public function testForwardsResolvedSubjectOnStorage(): void
    {
        $inner = new RecordingSubjectAwareStorages();
        $storages = new SubjectStorages(
            $inner,
            new FixedSubjectResolver(Subject::named(new SubjectId('u42')))
        );

        $storages->storage(new StorageNamespace('ui.table'));

        self::assertSame('ui.table', $inner->last_namespace);
        self::assertTrue($inner->last_subject?->isNamed());
        self::assertSame('u42', $inner->last_subject?->id()->storageSegment());
    }

    public function testStorageForUsesExplicitSubjectId(): void
    {
        $inner = new RecordingSubjectAwareStorages();
        $storages = new SubjectStorages(
            $inner,
            new FixedSubjectResolver(Subject::anonymous())
        );

        $storages->storageFor(new StorageNamespace('ui.table'), new SubjectId('u7'));

        self::assertTrue($inner->last_subject?->isNamed());
        self::assertSame('u7', $inner->last_subject?->id()->storageSegment());
    }

    public function testResolvesSubjectAtAccessTime(): void
    {
        $inner = new RecordingSubjectAwareStorages();
        $resolver = new CountingSubjectResolver(Subject::anonymous());
        $storages = new SubjectStorages($inner, $resolver);

        self::assertSame(0, $resolver->invocation_count);

        $storages->storage(new StorageNamespace('ui.table'));

        self::assertSame(1, $resolver->invocation_count);
        self::assertTrue($inner->last_subject?->isAnonymous());
    }

    public function testResolvesSubjectOnlyOncePerInstance(): void
    {
        $inner = new RecordingSubjectAwareStorages();
        $resolver = new CountingSubjectResolver(Subject::named(new SubjectId('u42')));
        $storages = new SubjectStorages($inner, $resolver);

        $storages->storage(new StorageNamespace('ui.table'));
        $storages->storage(new StorageNamespace('ui.table'));

        self::assertSame(1, $resolver->invocation_count);
    }
}

final readonly class FixedSubjectResolver implements SubjectResolver
{
    public function __construct(private Subject $subject)
    {
    }

    public function subject(): Subject
    {
        return $this->subject;
    }
}

final class CountingSubjectResolver implements SubjectResolver
{
    public int $invocation_count = 0;

    public function __construct(private readonly Subject $subject)
    {
    }

    public function subject(): Subject
    {
        ++$this->invocation_count;

        return $this->subject;
    }
}

final class RecordingSubjectAwareStorages implements SubjectAwareStorages
{
    public ?string $last_namespace = null;

    public ?Subject $last_subject = null;

    public function storageWithSubject(StorageNamespace $namespace, Subject $subject): Storage
    {
        $this->last_namespace = $namespace->value();
        $this->last_subject = $subject;

        return new StubSubjectStorage();
    }
}

final class StubSubjectStorage implements Storage
{
    public function has(string $key): bool
    {
        return false;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function set(string $key, mixed $value): void
    {
    }

    public function delete(string $key): void
    {
    }

    public function clear(): void
    {
    }
}
