<?php
declare(strict_types=1);

/* Copyright (c) 2019 Nils Haagen <nils.haagen@concepts-and-training.de> Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Component\Tree\Node;

/**
 * This describes a tree node with an byline with additional information
 * about this node
 */
interface Bylined extends Simple
{
    /**
     * The byline string that will be displayed as additional
     * information to the current node
     * @return string
     */
    public function getBylined() : string;
}
