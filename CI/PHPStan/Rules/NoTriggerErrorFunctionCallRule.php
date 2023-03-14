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
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ClosureType;

final class NoTriggerErrorFunctionCallRule implements Rule
{
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @param FuncCall $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name instanceof \PhpParser\Node\Name && strtolower((string) $node->name) == 'trigger_error') {
            // This is easy, the function is called directly: trigger_error('foo');
            return [
                RuleErrorBuilder::message(
                    'You must not call trigger_error()'
                )->build(),
            ];
        }

        if ($node->name instanceof \PhpParser\Node\Expr\Variable) {
            $infered_type = $scope->getType($node->name);
            if ($infered_type->isCallable()->yes()) {
                if ($infered_type instanceof ConstantStringType) {
                    // This covers: $x = 'trigger_error'; $x('Foo');
                    return [
                        RuleErrorBuilder::message(
                            'You must not call trigger_error() '
                        )->build(),
                    ];
                }

                if ($infered_type instanceof ClosureType) {
                    // This covers: $x = trigger_error(...); $x('Foo');
                    // TODO: How to we get the name of the first class calling?
                    return [
                        RuleErrorBuilder::message(
                            'You must not call trigger_error() ' . print_r($infered_type, true)
                        )->build(),
                    ];
                }
            }
        }

        return [];
    }
}
