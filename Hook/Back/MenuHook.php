<?php

namespace ColissimoLabel\Hook\Back;

use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

class MenuHook extends BaseHook
{
    public static function getSubscribedHooks(): array
    {
        return [
            'main.in-top-menu-items' => [
                ['type' => 'back', 'method' => 'onMainInTopMenuItems'],
            ],
        ];
    }

    public function onMainInTopMenuItems(HookRenderEvent $event): void
    {
        $event->add(
            $this->render('colissimo-label/hook/main.in.top.menu.items.html.twig', [])
        );
    }
}
