<?php

use ILIAS\GlobalScreen\Identification\IdentificationInterface;
use ILIAS\GlobalScreen\Scope\MainMenu\Provider\AbstractStaticMainMenuProvider;

/**
 * Class ilRepositoryGlobalScreenProvider
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilRepositoryGlobalScreenProvider extends AbstractStaticMainMenuProvider
{

    /**
     * @var IdentificationInterface
     */
    protected $top_item;


    /**
     * ilRepositoryGlobalScreenProvider constructor.
     *
     * @param \ILIAS\DI\Container $dic
     */
    public function __construct(\ILIAS\DI\Container $dic)
    {
        parent::__construct($dic);
        $this->top_item = $this->if->identifier('rep');
    }


    /**
     * Some other components want to provide Items for the main menu which are
     * located at the PD TopTitem by default. Therefore we have to provide our
     * TopTitem Identification for others
     *
     * @return IdentificationInterface
     */
    public function getTopItem() : IdentificationInterface
    {
        return $this->top_item;
    }


    /**
     * @inheritDoc
     */
    public function getStaticTopItems() : array
    {
        $dic = $this->dic;
        
        // fau: rootIsReduced - adapt repository link
        // Home Page
        $startItem = $this->mainmenu->topLinkItem($this->if->identifier('repository_public'))
            ->withTitle($this->dic->language()->txt("repository_public"))
            ->withAction('goto.php?target=cat_' . ilCust::get('ilias_repository_cat_id'))
            ->withPosition(1)
            ->withVisibilityCallable(
                function () use ($dic) {
                    return (bool) ($dic->user()->getId() == ANONYMOUS_USER_ID &&
                        $dic->access()->checkAccess('visible', '', ilCust::get('ilias_repository_cat_id')));
                }
            );
        
        
        return [$startItem, $this->mainmenu->topParentItem($this->getTopItem())
                    ->withTitle($this->dic->language()->txt("repository"))
                    ->withPosition(2)
                    ->withVisibilityCallable(
                        function () use ($dic) {
                            return (bool) ($dic->user()->getId() != ANONYMOUS_USER_ID &&
                                $dic->access()->checkAccess('visible', '', ilCust::get('ilias_repository_cat_id')));
                        }
                    )];
        // fau.
    }


    /**
     * @inheritDoc
     */
    public function getStaticSubItems() : array
    {
        $dic = $this->dic;

        $title = function () use ($dic) : string {
            try {
                // fau: rootIsReduced - adapt repository title
                if ($rep_id = ilCust::get("ilias_repository_cat_id")) {
                    $nd = $dic['tree']->getNodeData($rep_id);
                } else {
                    $nd = $dic['tree']->getNodeData(ROOT_FOLDER_ID);
                }

                $title = ($nd["title"] === "ILIAS" ? $dic->language()->txt("repository") : $nd["title"]);
                $icon = ilUtil::img(ilObject::_getIcon(ilObject::_lookupObjId($rep_id ? $rep_id : 1), "tiny"));
            } catch (InvalidArgumentException $e) {
                return "";
            }

            return $icon . $title . " - " . $dic->language()->txt("rep_main_page");
            // fau.
        };

        $action = function () : string {
            try {
                // fau: rootIsReduced - adapt repository link
                if ($rep_id = ilCust::get("ilias_repository_cat_id")) {
                    $static_link = ilLink::_getStaticLink($rep_id, 'cat', true);
                } else {
                    $static_link = ilLink::_getStaticLink(1, 'root', true);
                }
                // fau.
            } catch (InvalidArgumentException $e) {
                return "";
            }

            return $static_link;
        };

        $entries[] = $this->mainmenu->link($this->if->identifier('rep_main_page'))
            ->withTitle($title())
            ->withAction($action())
            ->withParent($this->getTopItem());

        // LastVisited
        $links = function () : array {
            $items = [];
            if (isset($this->dic['ilNavigationHistory'])) {
                $items = $this->dic['ilNavigationHistory']->getItems();
            }
            $links = [];
            reset($items);
            $cnt = 0;
            $first = true;

            foreach ($items as $k => $item) {
                if ($cnt >= 10) {
                    break;
                }

                // fau: rootIsReduced - don't repeat repository link in history
                if ($item["ref_id"] == ilCust::get("ilias_repository_cat_id")) {
                    continue;
                }
                // fau.
                if (!isset($item["ref_id"]) || !isset($_GET["ref_id"])
                    || ($item["ref_id"] != $_GET["ref_id"] || !$first)
                ) {            // do not list current item
                    $obj_id = ilObject::_lookupObjId($item["ref_id"]);
                    $icon = ilUtil::img(ilObject::_getIcon($obj_id, "tiny"));
                    $ititle = ilUtil::shortenText(strip_tags($item["title"]), 50, true); // #11023
                    $links[] = $this->mainmenu->link($this->if->identifier('last_visited_' . $obj_id))
                        ->withTitle($icon . " " . $ititle)
                        ->withAction($item["link"]);
                }
                $first = false;
            }

            return $links;
        };
        $entries[] = $this->mainmenu->linkList($this->if->identifier('last_visited'))
            ->withLinks($links)
            ->withTitle($this->dic->language()->txt('last_visited'))
            ->withParent($this->getTopItem())->withVisibilityCallable(
                function () use ($dic) {
                    return ($dic->user()->getId() != ANONYMOUS_USER_ID);
                }
            );

        return $entries;
    }
}
