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

use ILIAS\KeyValueStorage\Domain\Exception\StorageNotAvailableException;
use ILIAS\KeyValueStorage\Infrastructure\Factory;
use ILIAS\KeyValueStorage\Domain\Storage;
use ILIAS\KeyValueStorage\Infrastructure\StorageBackend;
use ILIAS\KeyValueStorage\Domain\StorageNamespace;
use ILIAS\KeyValueStorage\Port\StorageProvider;
use ILIAS\KeyValueStorage\Domain\Subject\Subject;
use ILIAS\KeyValueStorage\Domain\Subject\SubjectId;
use ILIAS\KeyValueStorage\Domain\Subject\SubjectResolver;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function testSessionReturnsStorageFromSessionProvider(): void
    {
        $plain = new StubStorage();
        $provider = new RecordingProvider(StorageBackend::SESSION, $plain);
        $factory = new Factory([$provider]);

        $storage = $factory->session()->storage(new StorageNamespace('ui.state'));

        self::assertSame($plain, $storage);
        self::assertSame('ui.state', $provider->last_storage_namespace);
        self::assertTrue($provider->last_storage_subject?->isAbsent());
    }

    public function testPersistentStorageForUsesNamedSubject(): void
    {
        $plain = new StubStorage();
        $provider = new RecordingProvider(StorageBackend::PERSISTENT, $plain);
        $factory = new Factory([$provider]);

        $storage = $factory->persistent()->storageFor(
            new StorageNamespace('export.job'),
            new SubjectId('u42')
        );

        self::assertSame($plain, $storage);
        self::assertTrue($provider->last_storage_subject?->isNamed());
        self::assertSame('u42', $provider->last_storage_subject?->id()->storageSegment());
    }

    public function testPersistentWithSubjectResolvesNamedSubjectAtAccessTime(): void
    {
        $plain = new StubStorage();
        $provider = new RecordingProvider(StorageBackend::PERSISTENT, $plain);
        $factory = new Factory([$provider]);
        $subject_resolver = new readonly class () implements SubjectResolver {
            public function subject(): Subject
            {
                return Subject::named(new SubjectId('u7'));
            }
        };

        $factory->persistentWithSubject($subject_resolver)->storage(new StorageNamespace('ui.table'));

        self::assertTrue($provider->last_storage_subject?->isNamed());
        self::assertSame('u7', $provider->last_storage_subject?->id()->storageSegment());
    }

    public function testSelectsProviderByLifetime(): void
    {
        $session_storage = new StubStorage();
        $persistent_storage = new StubStorage();
        $factory = new Factory([
            new RecordingProvider(StorageBackend::SESSION, $session_storage),
            new RecordingProvider(StorageBackend::PERSISTENT, $persistent_storage),
        ]);

        $namespace = new StorageNamespace('export.job');

        self::assertSame($session_storage, $factory->session()->storage($namespace));
        self::assertSame($persistent_storage, $factory->persistent()->storage($namespace));
    }

    public function testUsesLastProviderWhenBackendIsRegisteredTwice(): void
    {
        $first = new StubStorage();
        $second = new StubStorage();
        $factory = new Factory([
            new RecordingProvider(StorageBackend::SESSION, $first),
            new RecordingProvider(StorageBackend::SESSION, $second),
        ]);

        self::assertSame($second, $factory->session()->storage(new StorageNamespace('ui.state')));
    }

    public function testSessionThrowsWhenBackendIsNotContributed(): void
    {
        $factory = new Factory([
            new RecordingProvider(StorageBackend::PERSISTENT, new StubStorage()),
        ]);

        $this->expectException(StorageNotAvailableException::class);
        $this->expectExceptionMessage('No storage provider is registered for backend "session".');

        $factory->session();
    }

    public function testPersistentThrowsWhenBackendIsNotContributed(): void
    {
        $factory = new Factory([
            new RecordingProvider(StorageBackend::SESSION, new StubStorage()),
        ]);

        $this->expectException(StorageNotAvailableException::class);
        $this->expectExceptionMessage('No storage provider is registered for backend "persistent".');

        $factory->persistent();
    }
}

final class StubStorage implements Storage
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

final class RecordingProvider implements StorageProvider
{
    public ?string $last_storage_namespace = null;

    public ?Subject $last_storage_subject = null;

    public function __construct(
        private readonly StorageBackend $storage_backend,
        private readonly Storage $plain_storage
    ) {
    }

    public function backend(): StorageBackend
    {
        return $this->storage_backend;
    }

    public function storageWithSubject(StorageNamespace $namespace, Subject $subject): Storage
    {
        $this->last_storage_namespace = $namespace->value();
        $this->last_storage_subject = $subject;

        return $this->plain_storage;
    }
}
