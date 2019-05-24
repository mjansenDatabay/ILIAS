<?php declare(strict_types=1);

use ILIAS\GlobalScreen\Scope\Tool\Provider\AbstractDynamicToolProvider;
use ILIAS\NavigationContext\Stack\CalledContexts;
use ILIAS\NavigationContext\Stack\ContextCollection;

/**
 * Class ilMailGlobalScreenToolProvider
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilMailGlobalScreenToolProvider extends AbstractDynamicToolProvider
{
	/**
	 * @inheritDoc
	 */
	public function isInterestedInContexts(): ContextCollection
	{
		return $this->dic->navigationContext()->collection()->main();
	}

	/**
	 * @inheritDoc
	 */
	public function getToolsForContextStack(CalledContexts $called_contexts): array
	{
		$factory = $this->globalScreen()->tool();

		$identification = function ($id) {
			return $this->globalScreen()->identification()->fromSerializedIdentification($id);
		};

		$tools = [];

		$baseClass = (string) ($this->dic->http()->request()->getQueryParams()['baseClass'] ?? '');
		if (strtolower($baseClass) === 'ilmailgui') {
			$exp = new ilMailExplorer(new ilMailGUI(), 'showExplorer', $this->dic->user()->getId());

			if (!$exp->handleCommand()) {
				$tools[] = $factory
					->tool($identification('Mail|Tree'))
					->withTitle($this->dic->language()->txt("mail_folders"))
					->withContent($this->dic->ui()->factory()->legacy($exp->getHTML()));
			}
		}

		return $tools;
	}
}
