<?php

declare(strict_types=1);

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

require_once("vendor/composer/vendor/autoload.php");

use ILIAS\Data;
use PHPUnit\Framework\TestCase;
use ILIAS\Data\LanguageTag;
use ILIAS\Data\NotOKException;

/**
 * Testing the faytory of result objects
 *
 * @author Stefan Hecken <stefan.hecken@concepts-and-training.de>
 */
class DataFactoryTest extends TestCase
{
    /**
     * @var Data\Factory|null
     */
    private ?Data\Factory $f;

    protected function setUp(): void
    {
        $this->f = new Data\Factory();
    }

    protected function tearDown(): void
    {
        $this->f = null;
    }

    public function testOk(): void
    {
        $result = $this->f->ok(3.154);
        $this->assertInstanceOf(Data\Result::class, $result);
        $this->assertTrue($result->isOk());
        $this->assertFalse($result->isError());
    }

    public function testError(): void
    {
        $result = $this->f->error("Something went wrong");
        $this->assertInstanceOf(Data\Result::class, $result);
        $this->assertTrue($result->isError());
        $this->assertFalse($result->isOk());
    }

    public function testPassword(): void
    {
        $pwd = $this->f->password("secret");
        $this->assertInstanceOf(Data\Password::class, $pwd);
    }

    public function testAlphanumeric(): void
    {
        $dataType = $this->f->alphanumeric('someValue');
        $this->assertInstanceOf(Data\Alphanumeric::class, $dataType);
    }

    public function testPositiveInteger(): void
    {
        $dataType = $this->f->positiveInteger(100);
        $this->assertInstanceOf(Data\PositiveInteger::class, $dataType);
    }

    public function testDataSize1(): void
    {
        $dataType = $this->f->dataSize(10, "MB");
        $this->assertInstanceOf(Data\DataSize::class, $dataType);
    }

    public function testDataSize2(): void
    {
        $dataType = $this->f->dataSize("10G");
        $this->assertEquals(10, $dataType->getSize());
        $this->assertEquals(Data\DataSize::GiB, $dataType->getUnit());
        $this->assertEquals(10 * Data\DataSize::GiB, $dataType->inBytes());
        $this->assertInstanceOf(Data\DataSize::class, $dataType);
    }

    public function testLanguageTag(): void
    {
        $tag = $this->f->languageTag('de');
        $this->assertInstanceOf(LanguageTag::class, $tag);
    }

    public function testLanguageTagFailed(): void
    {
        $this->expectException(NotOKException::class);
        $this->f->languageTag('d$');
    }
}
