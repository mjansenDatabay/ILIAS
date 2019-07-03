<?php declare(strict_types=1);

use ILIAS\GlobalScreen\Scope\Tool\Provider\AbstractDynamicToolProvider;

/**
 * Class ilMailGlobalScreenToolProvider
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilMailGlobalScreenToolProvider extends AbstractDynamicToolProvider
{
    const SHOW_MAIL_FOLDERS_TOOL = 'show_mail_folders_tool';
	/**
	 * @inheritDoc
	 */
	public function isInterestedInContexts(): \ILIAS\GlobalScreen\Scope\Tool\Context\Stack\ContextCollection
	{
        return $this->context_collection->main()->repository()->administration();
	}

	/**
	 * @inheritDoc
	 */
	public function getToolsForContextStack(\ILIAS\GlobalScreen\Scope\Tool\Context\Stack\CalledContexts $called_contexts): array
	{
		$identification = function ($id) {
		    return $this->identification_provider->identifier($id);
		};

		$tools = [];

		$baseClass = (string) ($this->dic->http()->request()->getQueryParams()['baseClass'] ?? '');
		if (strtolower($baseClass) === 'ilmailgui') {
			$exp = new ilMailExplorer(new ilMailGUI(), $this->dic->user()->getId());

            $tools[] = $this->factory
                ->tool($identification('tree'))
                ->withTitle($this->dic->language()->txt("mail_folders"))
                ->withContent($this->dic->ui()->factory()->legacy($exp->getHTML()));
		}

		return $tools;
	}
}
