<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author  Niels Theen <ntheen@databay.de>
 */

namespace ILIAS\UI\Implementation\Component\Tree\Node;

use ILIAS\UI\Component\Tree\Node\Byline as BylineInterface;
use ILIAS\UI\Component\Tree\Node\Icon;

class Byline extends \ILIAS\UI\Implementation\Component\Tree\Node\Simple implements BylineInterface
{
    /**
     * @var string
     */
    private $byline;

    public function __construct(string $label, string $byline, \ILIAS\UI\Component\Symbol\Icon\Icon $icon = null)
    {
        parent::__construct($label, $icon);

        $this->byline = $byline;
    }

    public function getByline() : string
    {
        return $this->byline;
    }
}
