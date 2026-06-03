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

use ILIAS\KeyValueStorage\Application\Factory as FactoryInterface;
use ILIAS\KeyValueStorage\Application\Storages;
use ILIAS\KeyValueStorage\Application\SubjectStorages;
use ILIAS\KeyValueStorage\Domain\Exception\StorageNotAvailableException;
use ILIAS\KeyValueStorage\Domain\Subject\SubjectResolver;
use ILIAS\KeyValueStorage\Port\StorageProvider;

/**
 * Resolves storage instances from contributed providers.
 */
final readonly class Factory implements FactoryInterface
{
    /** @var array<string, StorageProvider> */
    private array $providers_by_backend;

    /**
     * @param list<StorageProvider> $providers
     */
    public function __construct(array $providers)
    {
        $providers_by_backend = [];
        foreach ($providers as $provider) {
            $providers_by_backend[$provider->backend()->value] = $provider;
        }

        $this->providers_by_backend = $providers_by_backend;
    }

    public function session(): Storages
    {
        return new DefaultStorages($this->provider(StorageBackend::SESSION));
    }

    public function persistent(): Storages
    {
        return new DefaultStorages($this->provider(StorageBackend::PERSISTENT));
    }

    public function sessionWithSubject(SubjectResolver $subject_resolver): Storages
    {
        return new SubjectStorages($this->provider(StorageBackend::SESSION), $subject_resolver);
    }

    public function persistentWithSubject(SubjectResolver $subject_resolver): Storages
    {
        return new SubjectStorages($this->provider(StorageBackend::PERSISTENT), $subject_resolver);
    }

    private function provider(StorageBackend $backend): StorageProvider
    {
        return $this->providers_by_backend[$backend->value]
            ?? throw new StorageNotAvailableException($backend);
    }
}
