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

namespace ILIAS\KeyValueStorage\Port;

use ILIAS\KeyValueStorage\Domain\StorageNamespace;
use ILIAS\KeyValueStorage\Domain\Subject\Subject;
use ILIAS\KeyValueStorage\Domain\Subject\SubjectId;

/**
 * Low-level persistence contract for one backend implementation.
 *
 * Values are transported as opaque strings; encoding is handled above this layer.
 * The {@see Subject} is transported on every call; the backend decides how to
 * encode or handle it.
 */
interface StoragePort
{
    public function has(StorageNamespace $namespace, string $key, Subject $subject): bool;

    public function read(StorageNamespace $namespace, string $key, Subject $subject): ?string;

    public function write(StorageNamespace $namespace, string $key, string $value, Subject $subject): void;

    public function remove(StorageNamespace $namespace, string $key, Subject $subject): void;

    public function clearNamespace(StorageNamespace $namespace, Subject $subject): void;

    /**
     * Removes all entries belonging to the given subject segment in this backend.
     */
    public function purgeSubject(SubjectId $subject): void;

    /**
     * Removes all entries belonging to the given subject segments in this backend.
     *
     * @param list<SubjectId> $subjects
     */
    public function purgeSubjects(array $subjects): void;
}
