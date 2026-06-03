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
use ILIAS\KeyValueStorage\Domain\Subject\Subject;
use ILIAS\KeyValueStorage\Domain\Subject\SubjectId;
use ILIAS\KeyValueStorage\Domain\Subject\SubjectResolver;
use ILIAS\KeyValueStorage\Port\SubjectAwareStorages;

/**
 * Resolves the current subject at runtime and exposes the consumer {@see Storages} API.
 *
 * Obtain through {@see Factory::sessionWithSubject()} or {@see Factory::persistentWithSubject()}.
 */
final class SubjectStorages implements Storages
{
    private ?Subject $resolved_subject = null;

    public function __construct(
        private readonly SubjectAwareStorages $inner,
        private readonly SubjectResolver $subject_resolver
    ) {
    }

    public function storage(StorageNamespace $namespace): Storage
    {
        return $this->inner->storageWithSubject($namespace, $this->resolveSubject());
    }

    public function storageFor(StorageNamespace $namespace, SubjectId $subject_id): Storage
    {
        return $this->inner->storageWithSubject($namespace, Subject::named($subject_id));
    }

    private function resolveSubject(): Subject
    {
        return $this->resolved_subject ??= $this->subject_resolver->subject();
    }
}
