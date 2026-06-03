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

namespace ILIAS;

use ILIAS\Component\Component;

class KeyValueStorage implements Component
{
    public function init(
        array | \ArrayAccess &$define,
        array | \ArrayAccess &$implement,
        array | \ArrayAccess &$use,
        array | \ArrayAccess &$contribute,
        array | \ArrayAccess &$seek,
        array | \ArrayAccess &$provide,
        array | \ArrayAccess &$pull,
        array | \ArrayAccess &$internal
    ): void {
        $define[] = KeyValueStorage\Application\Factory::class;
        $define[] = KeyValueStorage\Application\SubjectPurge::class;
        $define[] = KeyValueStorage\Port\SessionStoragePort::class;
        $define[] = KeyValueStorage\Port\PersistentStoragePort::class;

        $implement[KeyValueStorage\Application\Factory::class] = static fn() =>
            new KeyValueStorage\Infrastructure\Factory(
                $seek[KeyValueStorage\Port\StorageProvider::class]
            );

        $implement[KeyValueStorage\Application\SubjectPurge::class] = static fn() =>
            new KeyValueStorage\Infrastructure\SubjectPurgeAcrossPorts(
                $use[KeyValueStorage\Port\SessionStoragePort::class],
                $use[KeyValueStorage\Port\PersistentStoragePort::class]
            );

        $provide[KeyValueStorage\Port\StorageProviderFactory::class] = static fn() =>
            new KeyValueStorage\Infrastructure\StorageProviderFactory(
                new KeyValueStorage\Infrastructure\NamespacedStorageFactory(
                    new KeyValueStorage\Domain\KeyValidator(),
                    new KeyValueStorage\Infrastructure\ValueCodec()
                ),
                new KeyValueStorage\Infrastructure\RequestScopeCache()
            );
    }
}
