<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author  Niels Theen <ntheen@databay.de>
 */

namespace ILIAS\UI\Implementation\Component\Tree\Node;

use ILIAS\UI\Component\Tree\Node\Bylined as BylinedInterface;
use ILIAS\UI\Component\Tree\Node\Icon;

class Bylined extends \ILIAS\UI\Implementation\Component\Tree\Node\Simple implements BylinedInterface
{
    /**
     * @var string
     */
    private $bylined;

    public function __construct(string $label, string $bylined, \ILIAS\UI\Component\Symbol\Icon\Icon $icon = null)
    {
        parent::__construct($label, $icon);

        $this->bylined = $bylined;
    }

    public function getBylined() : string
    {
        return $this->bylined;
    }
}
