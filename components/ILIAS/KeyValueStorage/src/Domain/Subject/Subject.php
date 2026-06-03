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

namespace ILIAS\KeyValueStorage\Domain\Subject;

use ILIAS\KeyValueStorage\Domain\Subject\Internal\SubjectState;

/**
 * Subject scope transported to backend ports.
 * @internal Construct via named factories only.
 */
final readonly class Subject
{
    private function __construct(
        private SubjectState $state,
        private ?SubjectId $id
    ) {
        if ($state === SubjectState::Named && $id === null) {
            throw new \InvalidArgumentException('Named subject requires a SubjectId.');
        }

        if ($state !== SubjectState::Named && $id !== null) {
            throw new \InvalidArgumentException('SubjectId is only valid for a named subject.');
        }
    }

    public static function absent(): self
    {
        return new self(SubjectState::Absent, null);
    }

    public static function anonymous(): self
    {
        return new self(SubjectState::Anonymous, null);
    }

    public static function named(SubjectId $id): self
    {
        return new self(SubjectState::Named, $id);
    }

    public function isAbsent(): bool
    {
        return $this->state === SubjectState::Absent;
    }

    public function isAnonymous(): bool
    {
        return $this->state === SubjectState::Anonymous;
    }

    public function isNamed(): bool
    {
        return $this->state === SubjectState::Named;
    }

    public function id(): SubjectId
    {
        if (!$this->isNamed()) {
            throw new \LogicException('Subject has no id.');
        }

        return $this->id;
    }
}
