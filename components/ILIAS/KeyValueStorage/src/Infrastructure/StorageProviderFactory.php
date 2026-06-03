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

namespace ILIAS\KeyValueStorage\Infrastructure;

use ILIAS\KeyValueStorage\Port\PersistentStoragePort;
use ILIAS\KeyValueStorage\Port\SessionStoragePort;
use ILIAS\KeyValueStorage\Port\StoragePort;
use ILIAS\KeyValueStorage\Port\StorageProvider;
use ILIAS\KeyValueStorage\Port\StorageProviderFactory as StorageProviderFactoryInterface;

/**
 * @internal
 */
final readonly class StorageProviderFactory implements StorageProviderFactoryInterface
{
    public function __construct(
        private NamespacedStorageFactory $storage_factory,
        private RequestScopeCache $request_scope_cache
    ) {
    }

    public function session(SessionStoragePort $port): StorageProvider
    {
        return $this->create(StorageBackend::SESSION, $port);
    }

    public function persistent(PersistentStoragePort $port): StorageProvider
    {
        return $this->create(StorageBackend::PERSISTENT, $port);
    }

    private function create(StorageBackend $backend, StoragePort $port): StorageProvider
    {
        return new StorageProviderBridge(
            $backend,
            $port,
            $this->storage_factory,
            $this->request_scope_cache
        );
    }
}
