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

use ILIAS\KeyValueStorage\Infrastructure\StorageBackend;

/**
 * Contribution interface for components that supply a concrete storage backend.
 *
 * A provider is the {@see SubjectAwareStorages} accessor for exactly one backend.
 * Implementations must not be registered in this component; they are wired via
 * {@see StorageProviderFactory}.
 */
interface StorageProvider extends SubjectAwareStorages
{
    /**
     * The backend this provider serves.
     *
     * @internal Used to resolve the provider for {@see \ILIAS\KeyValueStorage\Application\Factory::session()} /
     *           {@see \ILIAS\KeyValueStorage\Application\Factory::persistent()}.
     */
    public function backend(): StorageBackend;
}
