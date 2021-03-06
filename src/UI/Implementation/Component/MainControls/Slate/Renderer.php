<?php

/* Copyright (c) 2017 Nils Haagen <nils.haagen@concepts.and-training.de> Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Implementation\Component\MainControls\Slate;

use ILIAS\UI\Implementation\Render\AbstractComponentRenderer;
use ILIAS\UI\Renderer as RendererInterface;
use ILIAS\UI\Component;
use ILIAS\UI\Component\MainControls\Slate as ISlate;

class Renderer extends AbstractComponentRenderer
{
    /**
     * @inheritdoc
     */
    public function render(Component\Component $component, RendererInterface $default_renderer)
    {
        $this->checkComponent($component);
        if ($component instanceof ISlate\Notification) {
            return $this->renderNotificationSlate($component, $default_renderer);
        }
        if ($component instanceof ISlate\Combined) {
            $contents = $this->getCombinedSlateContents($component);
        } else {
            $contents = $component->getContents();
        }
        return $this->renderSlate($component, $contents, $default_renderer);
    }

    protected function getCombinedSlateContents(
        ISlate\Slate $component
    ) {
        $f = $this->getUIFactory();
        $contents = [];
        foreach ($component->getContents() as $entry) {
            if ($entry instanceof ISlate\Slate && !$entry instanceof ISlate\Notification) {
                $init_state = 'disengaged';
                if ($entry->getEngaged()) {
                    $init_state = 'engaged';
                }
                $triggerer = $f->button()->bulky($entry->getSymbol(), $entry->getName(), '#')
                    ->withOnClick($entry->getToggleSignal())
                    ->withAdditionalOnloadCode(
                        function ($id) use ($init_state) {
                            return "$('#{$id}').addClass('{$init_state}');";
                        }
                    );

                $contents[] = $triggerer;
            }
            $contents[] = $entry;
        }
        return $contents;
    }

    protected function renderSlate(
        ISlate\Slate $component,
        $contents,
        RendererInterface $default_renderer
    )
    {
        $tpl = $this->getTemplate("Slate/tpl.slate.html", true, true);

        $tpl->setVariable('CONTENTS', $default_renderer->render($contents));

        if ($component->getEngaged()) {
            $tpl->touchBlock('engaged');
        } else {
            $tpl->touchBlock('disengaged');
        }

        $slate_signals = [
            'toggle'  => $component->getToggleSignal(),
            'engage'  => $component->getEngageSignal(),
            'replace' => $component->getReplaceSignal()
        ];
        $component     = $component->withAdditionalOnLoadCode(function ($id) use ($slate_signals) {
            $js = "fn = il.UI.maincontrols.slate.onSignal;";
            foreach ($slate_signals as $key => $signal) {
                $js .= "$(document).on('{$signal}', function(event, signalData) { fn('{$key}', event, signalData, '{$id}'); return false;});";
            }
            return $js;
        });
        $id            = $this->bindJavaScript($component);
        $tpl->setVariable('ID', $id);

        return $tpl->get();
    }

    protected function renderNotificationSlate(
        ISlate\Slate $component,
        RendererInterface $default_renderer
    ) {
        $contents = [];
        foreach ($component->getContents() as $entry) {
            $contents[] = $entry;
        }
        $tpl = $this->getTemplate("Slate/tpl.notification.html", true, true);
        $tpl->setVariable('NAME', $component->getName());
        $tpl->setVariable('CONTENTS', $default_renderer->render($contents));
        return $tpl->get();
    }

    /**
     * @inheritdoc
     */
    public function registerResources(\ILIAS\UI\Implementation\Render\ResourceRegistry $registry)
    {
        parent::registerResources($registry);
        $registry->register('./src/UI/templates/js/MainControls/slate.js');
    }

    /**
     * @inheritdoc
     */
    protected function getComponentInterfaceName()
    {
        return array(
            ISlate\Legacy::class,
            ISlate\Combined::class,
            ISlate\Notification::class
        );
    }
}
