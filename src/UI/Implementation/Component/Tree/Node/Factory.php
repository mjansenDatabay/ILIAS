<?php
declare(strict_types=1);

namespace ILIAS\UI\Implementation\Component\Tree\Node;

use ILIAS\UI\Component\Tree\Node as INode;
use ILIAS\UI\Component\Symbol\Icon\Icon as IIcon;
use ILIAS\UI\Component\Tree\Node\Byline as IByline;
use \ILIAS\UI\Implementation\Component\Tree\Node\Byline;

class Factory implements INode\Factory
{
	/**
	 * @inheritdoc
	 */
	public function simple(string $label, IIcon $icon=null): INode\Simple
	{
		return new Simple($label, $icon);
	}

    public function byline(string $label, string $byline, IIcon $icon = null) : IByline
    {
        return new Byline($label, $byline, $icon);
    }
}
