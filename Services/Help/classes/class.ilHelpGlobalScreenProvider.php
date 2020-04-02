<?php
/**
 * fau: mainMenuHelp - new class to add the help menu.
 */
use ILIAS\GlobalScreen\Identification\IdentificationInterface;
use ILIAS\GlobalScreen\Scope\MainMenu\Provider\AbstractStaticMainMenuProvider;

/**
 * Class ilHelpGlobalScreenProvider
 */
class ilHelpGlobalScreenProvider extends AbstractStaticMainMenuProvider
{

    /**
     * @var IdentificationInterface
     */
    protected $top_item;


    public function __construct(\ILIAS\DI\Container $dic)
    {
        parent::__construct($dic);
        $this->top_item = $this->if->identifier('help');
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
        $dic->language()->loadLanguageModule('help');

        // Help TopParentItem
        return [$this->mainmenu->topParentItem($this->getTopItem())
                    ->withTitle($this->dic->language()->txt("help"))
                    ->withPosition(4)
                ];
    }


    /**
     * @inheritDoc
     */
    public function getStaticSubItems() : array
    {
        $dic = $this->dic;
        
        // open online help (only shown when help has sections)
        $entries[] = $this->mainmenu->link($this->if->identifier('mm_help_online'))
            ->withTitle("<span> &nbsp; &nbsp; </span> " . $dic->language()->txt("help_open_online_help"))
            ->withAction("javascript:il.Help.listHelp(event, false);")
            ->withParent($this->getTopItem())
            ->withPosition(1)
            ->withNonAvailableReason($this->dic->ui()->factory()->legacy("{$this->dic->language()->txt('component_not_active')}"))
            ->withAvailableCallable(
                function () use ($dic) {
                    return (bool) $dic->help()->hasSections();
                }
            );

        // toggle tooltips
        $entries[] = $this->mainmenu->link($this->if->identifier('mm_help_tooltips'))
            ->withTitle('<span id="help_tt_switch_on" class="glyphicon glyphicon-ok"></span> ' . $this->dic->language()->txt("help_tooltips"))
            ->withAction("javascript:il.Help.switchTooltips(event);")
            ->withParent($this->getTopItem())
            ->withPosition(2);
        
        return $entries;
    }
}
