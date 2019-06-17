<?php


namespace ColissimoLabel\Hook\Back;

use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

class MenuHook extends BaseHook
{
    public function onMainInTopMenuItems(HookRenderEvent $event)
    {
        $event->add(
            $this->render('colissimo-label/hook/main.in.top.menu.items.html', [])
        );
    }
}
