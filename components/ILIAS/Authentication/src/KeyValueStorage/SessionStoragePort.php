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

namespace ILIAS\Authentication\KeyValueStorage;

use ILIAS\KeyValueStorage\Domain\StorageNamespace;
use ILIAS\KeyValueStorage\Domain\Subject\Subject;
use ILIAS\KeyValueStorage\Domain\Subject\SubjectId;
use ILIAS\KeyValueStorage\Port\SessionStoragePort as SessionStoragePortInterface;

/**
 * Session-backed implementation of the session storage port.
 *
 * Each entry is stored as a separate top-level session variable keyed by
 * {@see SESSION_ROOT}, namespace, and storage key. See Authentication ADR 0001.
 *
 * Absent and anonymous subjects use the logical namespace — the session already
 * isolates data per user. Named subjects append the segment as a namespace suffix.
 */
final readonly class SessionStoragePort implements SessionStoragePortInterface
{
    private const string SESSION_ROOT = '__ilias_kv_storage__';

    public function has(StorageNamespace $namespace, string $key, Subject $subject): bool
    {
        return \ilSession::has($this->buildSessionKey($this->physicalNamespace($namespace, $subject), $key));
    }

    public function read(StorageNamespace $namespace, string $key, Subject $subject): ?string
    {
        $value = \ilSession::get($this->buildSessionKey($this->physicalNamespace($namespace, $subject), $key));

        return \is_string($value) ? $value : null;
    }

    public function write(StorageNamespace $namespace, string $key, string $value, Subject $subject): void
    {
        \ilSession::set(
            $this->buildSessionKey($this->physicalNamespace($namespace, $subject), $key),
            $value
        );
    }

    public function remove(StorageNamespace $namespace, string $key, Subject $subject): void
    {
        \ilSession::clear($this->buildSessionKey($this->physicalNamespace($namespace, $subject), $key));
    }

    public function clearNamespace(StorageNamespace $namespace, Subject $subject): void
    {
        $prefix = self::SESSION_ROOT . '.' . $this->physicalNamespace($namespace, $subject)->value() . '.';
        $session = $_SESSION ?? [];

        foreach (\array_keys($session) as $session_key) {
            if (!\is_string($session_key) || !\str_starts_with($session_key, $prefix)) {
                continue;
            }

            \ilSession::clear($session_key);
        }
    }

    public function purgeSubject(SubjectId $subject): void
    {
        $this->purgeSubjects([$subject]);
    }

    public function purgeSubjects(array $subjects): void
    {
        // Session entries live in PHP sessions. User-bound sessions are destroyed
        // separately when user accounts are deleted (ilSession::_destroyByUserId).
    }

    private function physicalNamespace(StorageNamespace $logical, Subject $subject): StorageNamespace
    {
        if ($subject->isNamed()) {
            return new StorageNamespace($logical->value() . '.' . $subject->id()->storageSegment());
        }

        return $logical;
    }

    private function buildSessionKey(StorageNamespace $namespace, string $key): string
    {
        return self::SESSION_ROOT . '.' . $namespace->value() . '.' . $key;
    }
}
