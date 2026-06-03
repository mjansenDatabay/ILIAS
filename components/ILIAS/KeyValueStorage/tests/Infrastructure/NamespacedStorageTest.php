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

use ILIAS\KeyValueStorage\Infrastructure\NamespacedStorage;
use ILIAS\KeyValueStorage\Domain\KeyValidator;
use ILIAS\KeyValueStorage\Domain\StorageNamespace;
use ILIAS\KeyValueStorage\Port\StoragePort;
use ILIAS\KeyValueStorage\Domain\Subject\Subject;
use ILIAS\KeyValueStorage\Domain\Subject\SubjectId;
use ILIAS\KeyValueStorage\Infrastructure\ValueCodec;
use PHPUnit\Framework\TestCase;

class NamespacedStorageTest extends TestCase
{
    private RecordingStoragePort $port;
    private NamespacedStorage $storage;

    protected function setUp(): void
    {
        $this->port = new RecordingStoragePort();
        $this->storage = new NamespacedStorage(
            new StorageNamespace('ui.table'),
            Subject::absent(),
            $this->port,
            new KeyValidator(),
            new ValueCodec()
        );
    }

    public function testSetEncodesValueBeforeWritingToPort(): void
    {
        $this->storage->set('sort_column', 'title');

        self::assertSame(['sort_column' => '"title"'], $this->port->written_values);
    }

    public function testSetAndGetValue(): void
    {
        $this->storage->set('sort_column', 'title');

        self::assertTrue($this->storage->has('sort_column'));
        self::assertSame('title', $this->storage->get('sort_column'));
        self::assertSame(1, $this->port->read_count);
    }

    public function testGetReturnsDefaultWhenMissing(): void
    {
        self::assertSame('id', $this->storage->get('sort_column', 'id'));
        self::assertSame(1, $this->port->read_count);
    }

    public function testDeleteRemovesValue(): void
    {
        $this->storage->set('sort_column', 'title');

        $this->storage->delete('sort_column');

        self::assertFalse($this->storage->has('sort_column'));
        self::assertSame(1, $this->port->remove_count);
    }

    public function testClearRemovesNamespaceOnly(): void
    {
        $this->storage->set('sort_column', 'title');
        $this->port->write(
            new StorageNamespace('other.namespace'),
            'key',
            '"value"',
            Subject::absent()
        );

        $this->storage->clear();

        self::assertFalse($this->storage->has('sort_column'));
        self::assertSame(
            '"value"',
            $this->port->read(new StorageNamespace('other.namespace'), 'key', Subject::absent())
        );
        self::assertSame(1, $this->port->clear_namespace_count);
    }

    public function testForwardsSubjectToPort(): void
    {
        $subject = Subject::anonymous();
        $storage = new NamespacedStorage(
            new StorageNamespace('ui.table'),
            $subject,
            $this->port,
            new KeyValidator(),
            new ValueCodec()
        );

        $storage->set('sort_column', 'title');

        self::assertSame($subject, $this->port->last_subject);
    }

    public function testRejectsInvalidKeyOnSet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Storage key must not contain reserved characters');

        $this->storage->set('invalid/key', 'value');
    }

    public function testRejectsInvalidKeyOnGet(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->storage->get('invalid/key');
    }

    public function testRejectsKeyExceedingMaxLengthOnSet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Storage key must not exceed ' . KeyValidator::MAX_LENGTH . ' characters, got '
            . (KeyValidator::MAX_LENGTH + 1) . '.'
        );

        $this->storage->set(\str_repeat('k', KeyValidator::MAX_LENGTH + 1), 'value');
    }
}

final class RecordingStoragePort implements StoragePort
{
    /** @var array<string, string> */
    public array $written_values = [];

    public int $read_count = 0;

    public int $remove_count = 0;

    public int $clear_namespace_count = 0;

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

        if ($namespace->value() === 'ui.table') {
            $this->written_values[$key] = $value;
        }

        $this->data[$namespace->value()][$key] = $value;
    }

    public function remove(StorageNamespace $namespace, string $key, Subject $subject): void
    {
        ++$this->remove_count;
        unset($this->data[$namespace->value()][$key]);
    }

    public function clearNamespace(StorageNamespace $namespace, Subject $subject): void
    {
        ++$this->clear_namespace_count;
        unset($this->data[$namespace->value()]);
    }

    public function purgeSubject(SubjectId $subject): void
    {
    }

    public function purgeSubjects(array $subjects): void
    {
    }
}
