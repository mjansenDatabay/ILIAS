<?php declare(strict_types=1);

use ILIAS\GlobalScreen\Scope\Tool\Provider\AbstractDynamicToolProvider;
use ILIAS\NavigationContext\Stack\CalledContexts;
use ILIAS\NavigationContext\Stack\ContextCollection;

/**
 * Class ilForumGlobalScreenToolsProvider
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilForumGlobalScreenToolsProvider extends AbstractDynamicToolProvider
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

		$iff = function ($id) {
			return $this->globalScreen()->identification()->fromSerializedIdentification($id);
		};
		$l = function (string $content) {
			return $this->dic->ui()->factory()->legacy($content);
		};

		$tools = [];

		$cmdClass = (string) ($this->dic->http()->request()->getQueryParams()['cmdClass'] ?? '');
		$refid = (int) ($this->dic->http()->request()->getQueryParams()['ref_id'] ?? 0);
		$threadId = (int) ($this->dic->http()->request()->getQueryParams()['thr_pk'] ?? 0);
		if (strtolower($cmdClass) === 'ilobjforumgui' && $threadId > 0) {
			$isModerator = $this->dic->access()->checkAccess('moderate_frm', '', $refid);
			$thread = new ilForumTopic((int) $threadId, $isModerator);

			$exp = new ilForumExplorerGUI('frm_exp_' . $thread->getId(), new ilObjForumGUI("", $refid, true, false), 'viewThread');
			$exp->setThread($thread);

			if (!$exp->handleCommand()) {
				$tools[] = $factory
					->tool($iff('Forum|Tree'))
					->withTitle($this->dic->language()->txt("tree"))
					->withContent($l($exp->getHTML()));
			}
		}

		return $tools;
	}
}