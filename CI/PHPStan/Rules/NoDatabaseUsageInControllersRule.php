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

namespace ILIAS\CI\PHPStan\Rules;

use PHPStan\Rules\Rule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Type\ObjectType;
use ilDBInterface;
use ILIAS\CI\PHPStan\Services\ControllerDetermination;

final class NoDatabaseUsageInControllersRule implements Rule
{
    public function __construct(
        private readonly ControllerDetermination $determination
    ) {
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$scope->isInClass()) {
            return [];
        }

        if (!$this->determination->isController($scope->getClassReflection())) {
            return [];
        }

        $database_interface = new ObjectType(ilDBInterface::class);
        $current_object_type = $scope->getType($node->var);

        if (!$database_interface->isSuperTypeOf($current_object_type)->yes()) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'A controller class must not call any method of ' . ilDBInterface::class
            )->build(),
        ];
    }
}
