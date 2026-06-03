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

use ILIAS\KeyValueStorage\Infrastructure\NamespacedStorageFactory;
use ILIAS\KeyValueStorage\Infrastructure\RequestScopeCache;
use ILIAS\KeyValueStorage\Infrastructure\StorageBackend;
use ILIAS\KeyValueStorage\Infrastructure\StorageProviderFactory;
use ILIAS\KeyValueStorage\Domain\KeyValidator;
use ILIAS\KeyValueStorage\Domain\StorageNamespace;
use ILIAS\KeyValueStorage\Port\PersistentStoragePort;
use ILIAS\KeyValueStorage\Port\SessionStoragePort;
use ILIAS\KeyValueStorage\Port\StoragePort;
use ILIAS\KeyValueStorage\Domain\Subject\Subject;
use ILIAS\KeyValueStorage\Domain\Subject\SubjectId;
use ILIAS\KeyValueStorage\Infrastructure\ValueCodec;
use PHPUnit\Framework\TestCase;

class StorageProviderFactoryTest extends TestCase
{
    public function testSessionReturnsProviderWithInternalRequestCache(): void
    {
        $port = new FactoryTestRecordingPort();
        $provider = new StorageProviderFactory(
            new NamespacedStorageFactory(new KeyValidator(), new ValueCodec()),
            new RequestScopeCache()
        )->session($port);

        $storage = $provider->storageWithSubject(new StorageNamespace('export.job'), Subject::absent());
        $storage->set('state', 'running');

        self::assertSame('running', $storage->get('state'));
        self::assertSame('running', $storage->get('state'));
        self::assertSame(0, $port->read_count);
        self::assertSame(StorageBackend::SESSION, $provider->backend());
    }

    public function testPersistentReturnsProviderForPersistentBackend(): void
    {
        $port = new FactoryTestRecordingPort();
        $provider = new StorageProviderFactory(
            new NamespacedStorageFactory(new KeyValidator(), new ValueCodec()),
            new RequestScopeCache()
        )->persistent($port);

        self::assertSame(StorageBackend::PERSISTENT, $provider->backend());
    }
}

final class FactoryTestRecordingPort implements SessionStoragePort, PersistentStoragePort
{
    public int $read_count = 0;

    public function has(StorageNamespace $namespace, string $key, Subject $subject): bool
    {
        return false;
    }

    public function read(StorageNamespace $namespace, string $key, Subject $subject): ?string
    {
        ++$this->read_count;

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
    }

    public function purgeSubjects(array $subjects): void
    {
    }
}
