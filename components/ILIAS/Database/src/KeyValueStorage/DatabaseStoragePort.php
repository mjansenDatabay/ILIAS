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

namespace ILIAS\Database\KeyValueStorage;

use ILIAS\KeyValueStorage\Port\PersistentStoragePort;
use ILIAS\KeyValueStorage\Domain\StorageNamespace;
use ILIAS\KeyValueStorage\Domain\Subject\Subject;
use ILIAS\KeyValueStorage\Domain\Subject\SubjectId;
use ILIAS\KeyValueStorage\Domain\Subject\Exception\SubjectNotAddressableException;

/**
 * Database-backed implementation of the persistent storage port.
 *
 * The database connection is resolved lazily on first port use so this class can
 * be constructed during build/bootstrap phases where the global $DIC is not yet
 * available (for example `composer du`).
 *
 * Named subjects are stored in the dedicated {@code subject} column. Absent
 * subjects are persisted as an empty string because InnoDB primary-key columns
 * cannot be SQL {@code NULL}.
 */
final readonly class DatabaseStoragePort implements PersistentStoragePort
{
    private const string TABLE = 'il_kv_storage';

    public function __construct(
        private DatabaseConnection $database_connection
    ) {
    }

    public function has(StorageNamespace $namespace, string $key, Subject $subject): bool
    {
        $db = $this->database_connection->get();

        $result = $db->query(
            'SELECT EXISTS(SELECT 1 FROM ' . self::TABLE .
            ' WHERE namespace = ' . $db->quote($namespace->value(), \ilDBConstants::T_TEXT) .
            ' AND ' . $this->subjectCondition($db, $subject) .
            ' AND keyword = ' . $db->quote($key, \ilDBConstants::T_TEXT) . ') AS row_exists'
        );
        $row = $db->fetchAssoc($result);

        return (bool) ($row['row_exists'] ?? false);
    }

    public function read(StorageNamespace $namespace, string $key, Subject $subject): ?string
    {
        $db = $this->database_connection->get();

        $result = $db->query(
            'SELECT value FROM ' . self::TABLE .
            ' WHERE namespace = ' . $db->quote($namespace->value(), \ilDBConstants::T_TEXT) .
            ' AND ' . $this->subjectCondition($db, $subject) .
            ' AND keyword = ' . $db->quote($key, \ilDBConstants::T_TEXT)
        );
        $row = $db->fetchAssoc($result);

        if ($row === null) {
            return null;
        }

        return (string) $row['value'];
    }

    public function write(StorageNamespace $namespace, string $key, string $value, Subject $subject): void
    {
        $this->database_connection->get()->replace(
            self::TABLE,
            [
                'namespace' => [\ilDBConstants::T_TEXT, $namespace->value()],
                'subject' => [\ilDBConstants::T_TEXT, $this->persistedSubjectValue($subject)],
                'keyword' => [\ilDBConstants::T_TEXT, $key]
            ],
            [
                'value' => [\ilDBConstants::T_CLOB, $value]
            ]
        );
    }

    public function remove(StorageNamespace $namespace, string $key, Subject $subject): void
    {
        $db = $this->database_connection->get();

        $db->manipulate(
            'DELETE FROM ' . self::TABLE .
            ' WHERE namespace = ' . $db->quote($namespace->value(), \ilDBConstants::T_TEXT) .
            ' AND ' . $this->subjectCondition($db, $subject) .
            ' AND keyword = ' . $db->quote($key, \ilDBConstants::T_TEXT)
        );
    }

    public function clearNamespace(StorageNamespace $namespace, Subject $subject): void
    {
        $db = $this->database_connection->get();

        $db->manipulate(
            'DELETE FROM ' . self::TABLE .
            ' WHERE namespace = ' . $db->quote($namespace->value(), \ilDBConstants::T_TEXT) .
            ' AND ' . $this->subjectCondition($db, $subject)
        );
    }

    public function purgeSubject(SubjectId $subject): void
    {
        $this->purgeSubjects([$subject]);
    }

    public function purgeSubjects(array $subjects): void
    {
        if ($subjects === []) {
            return;
        }

        $db = $this->database_connection->get();
        $segments = array_map(
            static fn(SubjectId $subject): string => $subject->storageSegment(),
            $subjects
        );

        $db->manipulate(
            'DELETE FROM ' . self::TABLE .
            ' WHERE ' . $db->in('subject', $segments, false, \ilDBConstants::T_TEXT)
        );
    }

    private function persistedSubjectValue(Subject $subject): string
    {
        if ($subject->isAbsent()) {
            return '';
        }

        if ($subject->isAnonymous()) {
            throw new SubjectNotAddressableException(
                'Anonymous subject cannot be persisted by the database backend.'
            );
        }

        return $subject->id()->storageSegment();
    }

    private function subjectCondition(\ilDBInterface $db, Subject $subject): string
    {
        return $db->equals(
            'subject',
            $this->persistedSubjectValue($subject),
            \ilDBConstants::T_TEXT,
            $subject->isAbsent()
        );
    }
}
