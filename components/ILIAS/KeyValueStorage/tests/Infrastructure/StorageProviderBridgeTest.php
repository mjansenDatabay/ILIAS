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

use ILIAS\KeyValueStorage\Infrastructure\DefaultStorages;
use ILIAS\KeyValueStorage\Infrastructure\NamespacedStorageFactory;
use ILIAS\KeyValueStorage\Infrastructure\RequestScopeCache;
use ILIAS\KeyValueStorage\Infrastructure\StorageProviderBridge;
use ILIAS\KeyValueStorage\Domain\KeyValidator;
use ILIAS\KeyValueStorage\Infrastructure\StorageBackend;
use ILIAS\KeyValueStorage\Domain\StorageNamespace;
use ILIAS\KeyValueStorage\Port\StoragePort;
use ILIAS\KeyValueStorage\Domain\Subject\Subject;
use ILIAS\KeyValueStorage\Domain\Subject\SubjectId;
use ILIAS\KeyValueStorage\Infrastructure\ValueCodec;
use PHPUnit\Framework\TestCase;

class StorageProviderBridgeTest extends TestCase
{
    private BridgeRecordingStoragePort $port;
    private StorageProviderBridge $bridge;
    private DefaultStorages $storages;

    protected function setUp(): void
    {
        $this->port = new BridgeRecordingStoragePort();
        $this->bridge = new StorageProviderBridge(
            StorageBackend::PERSISTENT,
            $this->port,
            new NamespacedStorageFactory(new KeyValidator(), new ValueCodec()),
            new RequestScopeCache()
        );
        $this->storages = new DefaultStorages($this->bridge);
    }

    public function testBackendReturnsConfiguredBackend(): void
    {
        self::assertSame(StorageBackend::PERSISTENT, $this->bridge->backend());
    }

    public function testStorageMemoizesReadsWithinRequest(): void
    {
        $storage = $this->storages->storage(new StorageNamespace('export.job'));
        $storage->set('state', 'running');

        self::assertSame('running', $storage->get('state'));
        self::assertSame('running', $storage->get('state'));
        self::assertSame(0, $this->port->read_count);
    }

    public function testStorageForPassesNamedSubjectToPort(): void
    {
        $storage = $this->storages->storageFor(new StorageNamespace('export.job'), new SubjectId('u42'));
        $storage->set('state', 'running');

        self::assertTrue($this->port->last_subject?->isNamed());
        self::assertSame('u42', $this->port->last_subject?->id()->storageSegment());
    }

    public function testStorageWritesThroughToBackend(): void
    {
        $storage = $this->storages->storage(new StorageNamespace('export.job'));

        $storage->set('state', 'running');

        self::assertSame(['state' => '"running"'], $this->port->writes_for('export.job'));
    }

    public function testStorageUsesDistinctScopesPerSubject(): void
    {
        $namespace = new StorageNamespace('export.job');
        $anonymous = $this->bridge->storageWithSubject($namespace, Subject::anonymous());
        $named = $this->bridge->storageWithSubject($namespace, Subject::named(new SubjectId('u42')));

        $anonymous->set('state', 'anon');
        $named->set('state', 'named');

        self::assertSame('anon', $anonymous->get('state'));
        self::assertSame('named', $named->get('state'));
    }
}

final class BridgeRecordingStoragePort implements StoragePort
{
    public int $read_count = 0;

    public ?Subject $last_subject = null;

    /** @var array<string, array<string, string>> */
    private array $data = [];

    public function has(StorageNamespace $namespace, string $key, Subject $subject): bool
    {
        return \array_key_exists($key, $this->data[$namespace->value()] ?? []);
    }

    public function read(StorageNamespace $namespace, string $key, Subject $subject): ?string
    {
        ++$this->read_count;

        return $this->data[$namespace->value()][$key] ?? null;
    }

    public function write(StorageNamespace $namespace, string $key, string $value, Subject $subject): void
    {
        $this->last_subject = $subject;
        $this->data[$namespace->value()][$key] = $value;
    }

    public function remove(StorageNamespace $namespace, string $key, Subject $subject): void
    {
        unset($this->data[$namespace->value()][$key]);
    }

    public function clearNamespace(StorageNamespace $namespace, Subject $subject): void
    {
        unset($this->data[$namespace->value()]);
    }

    public function purgeSubject(SubjectId $subject): void
    {
    }

    public function purgeSubjects(array $subjects): void
    {
    }

    /** @return array<string, string> */
    public function writes_for(string $namespace): array
    {
        return $this->data[$namespace] ?? [];
    }
}
