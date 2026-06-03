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

namespace ILIAS\KeyValueStorage\Application;

use ILIAS\KeyValueStorage\Domain\Storage;
use ILIAS\KeyValueStorage\Domain\StorageNamespace;
use ILIAS\KeyValueStorage\Domain\Subject\SubjectId;

/**
 * Consumer access to namespace-scoped storages of one lifetime (session or persistent).
 *
 * Subject scoping is expressed through the method chosen.
 */
interface Storages
{
    /**
     * Unscoped storage (logical namespace only).
     */
    public function storage(StorageNamespace $namespace): Storage;

    /**
     * Storage scoped to one named actor segment.
     */
    public function storageFor(StorageNamespace $namespace, SubjectId $subject_id): Storage;
}
