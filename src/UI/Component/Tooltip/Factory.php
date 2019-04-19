<?php declare(strict_types=1);

namespace ILIAS\UI\Component\Tooltip;

use ILIAS\UI\Component\Component;

/**
 * Interface Factory
 * @package ILIAS\UI\Component\Tooltip
 * @author Niels Theen <ntheen@databay.de>
 * @author Colin Kiegel <kiegel@qualitus.de>
 * @author Michael Jansen <mjansen@databay.de>
 */
interface Factory
{
	/**
	 * ---
	 * description:
	 *   purpose: >
	 *      Standard Tooltips are used to display other components.
	 *      Tooltips should give the users additional information about a
	 *      displayed component.
	 *      They can be used to create visually appealing prompts
	 *      that grab user attentions when hovering or clicking other components,
	 *      e.g. input fields in a form, tabs etc..
	 *   composition: >
	 *      A Standard Tooltip displays the UI components passed as argument on creation.
	 *      A tooltip consists of a placement, the UI components to be displayed
	 *      and the Signal the tooltip reacts to.
	 * rules:
	 *   usage:
	 *      1: >
	 *          Standard Tooltips MUST be created with an array of \ILIAS\UI\Component\Component
	 *      2: >
	 *          Standard Tooltips MUST NOT contain complex or large components
	 * ---
	 *
	 * @param Component[] $contents An array of components that will be displayed in the tooltip.
	 * @return \ILIAS\UI\Component\Tooltip\Standard
	 */
	public function standard(array $contents): \ILIAS\UI\Component\Tooltip\Standard;
}
