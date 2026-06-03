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

namespace ILIAS\Tests\KeyValueStorage\Domain\Subject;

use ILIAS\KeyValueStorage\Domain\Subject\SubjectId;
use PHPUnit\Framework\TestCase;

class SubjectIdTest extends TestCase
{
    public function testAcceptsConsumerDefinedSegment(): void
    {
        $subject = new SubjectId('u42');

        self::assertSame('u42', $subject->storageSegment());
    }

    public function testAcceptsArbitraryValidSegment(): void
    {
        $subject = new SubjectId('course_7_admin');

        self::assertSame('course_7_admin', $subject->storageSegment());
    }

    public function testRejectsEmptySegment(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Subject segment must not be empty.');

        new SubjectId('');
    }

    public function testRejectsInvalidSegment(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Subject segment must be a lowercase identifier');

        new SubjectId('User-42');
    }

    public function testRejectsSegmentExceedingMaxLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Subject segment must not exceed ' . SubjectId::MAX_LENGTH . ' characters, got '
            . (SubjectId::MAX_LENGTH + 1) . '.'
        );

        new SubjectId('a' . \str_repeat('b', SubjectId::MAX_LENGTH));
    }
}
