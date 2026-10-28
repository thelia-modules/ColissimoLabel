<?php

namespace ColissimoLabel\Tests;

use ColissimoLabel\Controller\Admin\BordereauController;
use ColissimoLabel\Controller\Admin\OrderController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The actions that delete or create files only answer a POST, so that a link, a crawler or a prefetch cannot trigger them.
 */
class RouteContractTest extends TestCase
{
    public static function stateChangingActions(): iterable
    {
        yield 'label deletion' => [OrderController::class, 'deleteLabelAction', '/label/delete'];
        yield 'bordereau generation' => [BordereauController::class, 'generateBordereauAction', '/bordereau/generate'];
        yield 'bordereau deletion' => [BordereauController::class, 'deleteBordereauAction', '/bordereau/delete'];
    }

    #[DataProvider('stateChangingActions')]
    public function testStateChangingActionsOnlyAnswerPost(string $controller, string $action, string $path): void
    {
        $attributes = (new \ReflectionMethod($controller, $action))->getAttributes(Route::class);

        self::assertCount(1, $attributes);

        $route = $attributes[0]->newInstance();

        self::assertSame($path, $route->getPath());
        self::assertSame(['POST'], $route->getMethods());
    }
}
