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

namespace ILIAS\Tests\Database\KeyValueStorage;

use ILIAS\Database\KeyValueStorage\DatabaseConnection;
use ILIAS\Database\KeyValueStorage\DatabaseStoragePort;
use ILIAS\KeyValueStorage\Domain\StorageNamespace;
use ILIAS\KeyValueStorage\Domain\Subject\Subject;
use ILIAS\KeyValueStorage\Domain\Subject\SubjectId;
use ILIAS\KeyValueStorage\Domain\Subject\Exception\SubjectNotAddressableException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DatabaseStoragePortTest extends TestCase
{
    public function testConstructDoesNotResolveDatabaseConnection(): void
    {
        $database_connection = $this->createMock(DatabaseConnection::class);
        $database_connection->expects($this->never())
            ->method('get');

        new DatabaseStoragePort($database_connection);
    }

    public function testWriteResolvesDatabaseConnectionOnce(): void
    {
        $db = $this->createMock(\ilDBInterface::class);
        $db->expects($this->once())
            ->method('replace')
            ->with(
                'il_kv_storage',
                [
                    'namespace' => [\ilDBConstants::T_TEXT, 'ui.table'],
                    'subject' => [\ilDBConstants::T_TEXT, ''],
                    'keyword' => [\ilDBConstants::T_TEXT, 'sort_column'],
                ],
                [
                    'value' => [\ilDBConstants::T_CLOB, 'encoded'],
                ]
            );

        $database_connection = $this->createMock(DatabaseConnection::class);
        $database_connection->expects($this->once())
            ->method('get')
            ->willReturn($db);

        $port = new DatabaseStoragePort($database_connection);
        $port->write(new StorageNamespace('ui.table'), 'sort_column', 'encoded', Subject::absent());
    }

    public function testWriteStoresNamedSubjectInSubjectColumn(): void
    {
        $db = $this->createMock(\ilDBInterface::class);
        $db->expects($this->once())
            ->method('replace')
            ->with(
                'il_kv_storage',
                [
                    'namespace' => [\ilDBConstants::T_TEXT, 'ui.table'],
                    'subject' => [\ilDBConstants::T_TEXT, 'u42'],
                    'keyword' => [\ilDBConstants::T_TEXT, 'sort_column'],
                ],
                [
                    'value' => [\ilDBConstants::T_CLOB, 'encoded'],
                ]
            );

        $database_connection = $this->createMock(DatabaseConnection::class);
        $database_connection->expects($this->once())
            ->method('get')
            ->willReturn($db);

        $port = new DatabaseStoragePort($database_connection);
        $port->write(
            new StorageNamespace('ui.table'),
            'sort_column',
            'encoded',
            Subject::named(new SubjectId('u42'))
        );
    }

    public function testAnonymousSubjectCannotBePersisted(): void
    {
        $port = new DatabaseStoragePort($this->createMock(DatabaseConnection::class));

        $this->expectException(SubjectNotAddressableException::class);
        $this->expectExceptionMessage('Anonymous subject cannot be persisted');

        $port->write(
            new StorageNamespace('ui.table'),
            'sort_column',
            'encoded',
            Subject::anonymous()
        );
    }

    public function testReadReturnsStoredValue(): void
    {
        $db = $this->createMock(\ilDBInterface::class);
        $db->expects($this->once())
            ->method('query')
            ->with(
                "SELECT value FROM il_kv_storage WHERE namespace = 'ui.table' "
                . "AND (subject = '' OR subject IS NULL) AND keyword = 'sort_column'"
            )
            ->willReturn($this->createStub(\ilDBStatement::class));
        $db->expects($this->once())
            ->method('fetchAssoc')
            ->willReturn(['value' => 'encoded']);
        $db->method('quote')->willReturnCallback(static fn(string $value): string => "'" . $value . "'");
        $db->method('equals')->willReturnCallback(
            static fn(string $column, string $value, string $type, bool $empty_or_null = false): string =>
                $empty_or_null ? "($column = '' OR $column IS NULL)" : "$column = '$value'"
        );

        $port = $this->createPort($db);

        self::assertSame(
            'encoded',
            $port->read(new StorageNamespace('ui.table'), 'sort_column', Subject::absent())
        );
    }

    public function testReadReturnsNullWhenRowMissing(): void
    {
        $db = $this->createMock(\ilDBInterface::class);
        $db->expects($this->once())
            ->method('query')
            ->willReturn($this->createStub(\ilDBStatement::class));
        $db->expects($this->once())
            ->method('fetchAssoc')
            ->willReturn(null);
        $db->method('equals')->willReturn("(subject = '' OR subject IS NULL)");

        $port = $this->createPort($db);

        self::assertNull($port->read(new StorageNamespace('ui.table'), 'missing', Subject::absent()));
    }

    public function testHasUsesExistsQuery(): void
    {
        $db = $this->createMock(\ilDBInterface::class);
        $db->expects($this->once())
            ->method('query')
            ->with(
                "SELECT EXISTS(SELECT 1 FROM il_kv_storage WHERE namespace = 'ui.table' "
                . "AND (subject = '' OR subject IS NULL) AND keyword = 'sort_column') AS row_exists"
            )
            ->willReturn($this->createStub(\ilDBStatement::class));
        $db->expects($this->once())
            ->method('fetchAssoc')
            ->willReturn(['row_exists' => 1]);
        $db->method('quote')->willReturnCallback(static fn(string $value): string => "'" . $value . "'");
        $db->method('equals')->willReturnCallback(
            static fn(string $column, string $value, string $type, bool $empty_or_null = false): string =>
                $empty_or_null ? "($column = '' OR $column IS NULL)" : "$column = '$value'"
        );

        $port = $this->createPort($db);

        self::assertTrue($port->has(new StorageNamespace('ui.table'), 'sort_column', Subject::absent()));
    }

    public function testHasReturnsFalseWhenExistsIsZero(): void
    {
        $db = $this->createMock(\ilDBInterface::class);
        $db->expects($this->once())
            ->method('query')
            ->willReturn($this->createStub(\ilDBStatement::class));
        $db->expects($this->once())
            ->method('fetchAssoc')
            ->willReturn(['row_exists' => 0]);
        $db->method('equals')->willReturn("(subject = '' OR subject IS NULL)");

        $port = $this->createPort($db);

        self::assertFalse($port->has(new StorageNamespace('ui.table'), 'missing', Subject::absent()));
    }

    public function testHasReturnsFalseWhenResultRowMissing(): void
    {
        $db = $this->createMock(\ilDBInterface::class);
        $db->expects($this->once())
            ->method('query')
            ->willReturn($this->createStub(\ilDBStatement::class));
        $db->expects($this->once())
            ->method('fetchAssoc')
            ->willReturn(null);
        $db->method('equals')->willReturn("(subject = '' OR subject IS NULL)");

        $port = $this->createPort($db);

        self::assertFalse($port->has(new StorageNamespace('ui.table'), 'missing', Subject::absent()));
    }

    public function testRemoveDeletesByNamespaceSubjectAndKey(): void
    {
        $db = $this->createMock(\ilDBInterface::class);
        $db->expects($this->once())
            ->method('manipulate')
            ->with(
                "DELETE FROM il_kv_storage WHERE namespace = 'ui.table' "
                . "AND (subject = '' OR subject IS NULL) AND keyword = 'sort_column'"
            );
        $db->method('quote')->willReturnCallback(static fn(string $value): string => "'" . $value . "'");
        $db->method('equals')->willReturn("(subject = '' OR subject IS NULL)");

        $port = $this->createPort($db);
        $port->remove(new StorageNamespace('ui.table'), 'sort_column', Subject::absent());
    }

    public function testClearNamespaceDeletesByNamespaceAndSubject(): void
    {
        $db = $this->createMock(\ilDBInterface::class);
        $db->expects($this->once())
            ->method('manipulate')
            ->with(
                "DELETE FROM il_kv_storage WHERE namespace = 'ui.table' AND subject = 'u42'"
            );

        $db->method('quote')->willReturnCallback(static fn(string $value): string => "'" . $value . "'");
        $db->method('equals')->willReturn("subject = 'u42'");

        $port = $this->createPort($db);
        $port->clearNamespace(
            new StorageNamespace('ui.table'),
            Subject::named(new SubjectId('u42'))
        );
    }

    public function testPurgeSubjectDeletesBySubjectColumn(): void
    {
        $db = $this->createMock(\ilDBInterface::class);
        $db->expects($this->once())
            ->method('in')
            ->with('subject', ['u42'], false, \ilDBConstants::T_TEXT)
            ->willReturn("subject IN ('u42')");
        $db->expects($this->once())
            ->method('manipulate')
            ->with(
                "DELETE FROM il_kv_storage WHERE subject IN ('u42')"
            );

        $port = $this->createPort($db);
        $port->purgeSubject(new SubjectId('u42'));
    }

    public function testPurgeSubjectsDeletesAllMatchingSubjectsInOneStatement(): void
    {
        $db = $this->createMock(\ilDBInterface::class);
        $db->expects($this->once())
            ->method('in')
            ->with('subject', ['u42', 'u43'], false, \ilDBConstants::T_TEXT)
            ->willReturn("subject IN ('u42', 'u43')");
        $db->expects($this->once())
            ->method('manipulate')
            ->with(
                "DELETE FROM il_kv_storage WHERE subject IN ('u42', 'u43')"
            );

        $port = $this->createPort($db);
        $port->purgeSubjects([new SubjectId('u42'), new SubjectId('u43')]);
    }

    public function testPurgeSubjectsIgnoresEmptyList(): void
    {
        $database_connection = $this->createMock(DatabaseConnection::class);
        $database_connection->expects($this->never())->method('get');

        new DatabaseStoragePort($database_connection)->purgeSubjects([]);
    }

    private function createPort(MockObject&\ilDBInterface $db): DatabaseStoragePort
    {
        $database_connection = $this->createMock(DatabaseConnection::class);
        $database_connection->expects($this->once())
            ->method('get')
            ->willReturn($db);

        return new DatabaseStoragePort($database_connection);
    }
}
