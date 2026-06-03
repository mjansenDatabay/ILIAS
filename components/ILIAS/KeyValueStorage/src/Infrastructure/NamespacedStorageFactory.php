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

use ILIAS\KeyValueStorage\Domain\KeyValidator;
use ILIAS\KeyValueStorage\Domain\Storage;
use ILIAS\KeyValueStorage\Domain\StorageNamespace;
use ILIAS\KeyValueStorage\Port\StoragePort;
use ILIAS\KeyValueStorage\Domain\Subject\Subject;
use ILIAS\KeyValueStorage\Infrastructure\ValueCodec;

/**
 * Creates namespace-scoped storage instances for backend ports.
 *
 * @internal
 */
readonly class NamespacedStorageFactory
{
    public function __construct(
        private KeyValidator $key_validator,
        private ValueCodec $value_codec
    ) {
    }

    public function create(StorageNamespace $namespace, Subject $subject, StoragePort $port): Storage
    {
        return new NamespacedStorage($namespace, $subject, $port, $this->key_validator, $this->value_codec);
    }
}
