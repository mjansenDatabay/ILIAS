<?php declare(strict_types=1);
/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilTermsOfServiceCriterionTypeFactoryTest
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilTermsOfServiceCriterionTypeFactoryTest extends ilTermsOfServiceBaseTest
{
    /**
     * @return ilTermsOfServiceCriterionTypeFactory
     * @throws ReflectionException
     */
    public function testInstanceCanBeCreated() : ilTermsOfServiceCriterionTypeFactory
    {
        $dataCache = $this
            ->getMockBuilder(ilObjectDataCache::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $rbacReview = $this
            ->getMockBuilder(ilRbacReview::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $criterionTypeFactory = new ilTermsOfServiceCriterionTypeFactory($rbacReview, $dataCache);

        $this->assertInstanceOf(ilTermsOfServiceCriterionTypeFactory::class, $criterionTypeFactory);

        return $criterionTypeFactory;
    }

    /**
     * @depends testInstanceCanBeCreated
     * @param ilTermsOfServiceCriterionTypeFactory $criterionTypeFactory
     */
    public function testFactoryReturnsValidCriteriaWhenRequested(
        ilTermsOfServiceCriterionTypeFactory $criterionTypeFactory
    ) : void {
        $this->assertCount(2, $criterionTypeFactory->getTypesByIdentMap());
    }

    /**
     * @depends testInstanceCanBeCreated
     * @param ilTermsOfServiceCriterionTypeFactory $criterionTypeFactory
     */
    public function testKeysOfCriteriaCollectionMatchTheRespectiveTypeIdent(
        ilTermsOfServiceCriterionTypeFactory $criterionTypeFactory
    ) : void {
        $criteria = $criterionTypeFactory->getTypesByIdentMap();

        $this->assertEquals(
            array_keys($criteria),
            array_values(array_map(function (ilTermsOfServiceCriterionType $criterion) {
                return $criterion->getTypeIdent();
            }, $criteria))
        );
    }

    /**
     * @depends testInstanceCanBeCreated
     * @param ilTermsOfServiceCriterionTypeFactory $criterionTypeFactory
     * @throws ilTermsOfServiceCriterionTypeNotFoundException
     */
    public function testCriterionIsReturnedIfRequestedByTypeIdent(
        ilTermsOfServiceCriterionTypeFactory $criterionTypeFactory
    ) {
        foreach ($criterionTypeFactory->getTypesByIdentMap() as $criterion) {
            $this->assertEquals($criterion, $criterionTypeFactory->findByTypeIdent($criterion->getTypeIdent()));
        }
    }

    /**
     * @depends testInstanceCanBeCreated
     * @param ilTermsOfServiceCriterionTypeFactory $criterionTypeFactory
     * @throws ilTermsOfServiceCriterionTypeNotFoundException
     */
    public function testExceptionIsRaisedIfUnsupportedCriterionIsRequested(
        ilTermsOfServiceCriterionTypeFactory $criterionTypeFactory
    ) : void {
        $this->expectException(ilTermsOfServiceCriterionTypeNotFoundException::class);

        $criterionTypeFactory->findByTypeIdent('phpunit');
    }

    /**
     * @depends testInstanceCanBeCreated
     * @param ilTermsOfServiceCriterionTypeFactory $criterionTypeFactory
     * @throws ilTermsOfServiceCriterionTypeNotFoundException
     */
    public function testNullCriterionIsReturnedAsFallbackIfUnsupportedCriterionIsRequested(
        ilTermsOfServiceCriterionTypeFactory $criterionTypeFactory
    ) : void {
        $this->assertInstanceOf(
            ilTermsOfServiceNullCriterion::class,
            $criterionTypeFactory->findByTypeIdent('phpunit', true)
        );
    }
}