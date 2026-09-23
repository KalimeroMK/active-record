<?php

declare(strict_types=1);

namespace Yiisoft\ActiveRecord\Tests;

use LogicException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\ActiveRecord\Event\BeforeLazyRelationLoad;
use Yiisoft\ActiveRecord\Event\EventDispatcherProvider;
use Yiisoft\ActiveRecord\Event\Guard\LazyLoadGuard;
use Yiisoft\ActiveRecord\Event\Guard\LazyLoadGuardMode;
use Yiisoft\ActiveRecord\Tests\Stubs\ActiveRecord\CustomerEventsModel;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\Test\Support\Log\SimpleLogger;

abstract class LazyLoadGuardTest extends TestCase
{
    public function setUp(): void
    {
        EventDispatcherProvider::reset();
    }

    public function testLazyLoadDispatchesEvent(): void
    {
        $events = [];

        EventDispatcherProvider::set(
            CustomerEventsModel::class,
            new SimpleEventDispatcher(
                static function (object $event) use (&$events): void {
                    if ($event instanceof BeforeLazyRelationLoad) {
                        $events[] = $event->relationName;
                    }
                },
            ),
        );

        $customer = CustomerEventsModel::query()->findByPk(1);
        $customer->getOrders();

        $this->assertSame(['orders'], $events);
    }

    public function testEagerLoadedRelationDoesNotDispatchEvent(): void
    {
        $events = [];

        EventDispatcherProvider::set(
            CustomerEventsModel::class,
            new SimpleEventDispatcher(
                static function (object $event) use (&$events): void {
                    if ($event instanceof BeforeLazyRelationLoad) {
                        $events[] = $event->relationName;
                    }
                },
            ),
        );

        $customers = CustomerEventsModel::query()->with('orders')->all();

        foreach ($customers as $customer) {
            $customer->getOrders();
        }

        $this->assertSame([], $events);
    }

    public function testLazyLoadWithEventPrevention(): void
    {
        EventDispatcherProvider::set(
            CustomerEventsModel::class,
            new SimpleEventDispatcher(
                static function (object $event): void {
                    if ($event instanceof BeforeLazyRelationLoad) {
                        $event->returnValue([]);
                        $event->preventDefault();
                    }
                },
            ),
        );

        $customer = CustomerEventsModel::query()->findByPk(1);

        $this->assertSame([], $customer->getOrders());
    }

    public function testLazyLoadWorksWithoutGuardRegistered(): void
    {
        $customer = CustomerEventsModel::query()->findByPk(1);

        $this->assertCount(1, $customer->getOrders());
    }

    public function testModeLogWritesWarningWithContext(): void
    {
        $logger = new SimpleLogger();
        $guard = new LazyLoadGuard(LazyLoadGuardMode::Log, $logger);

        $this->registerGuard(CustomerEventsModel::class, $guard);

        $customer = CustomerEventsModel::query()->findByPk(1);
        $customer->getOrders();

        $messages = $logger->getMessages();

        $this->assertCount(1, $messages);
        $this->assertSame('warning', $messages[0]['level']);
        $this->assertStringContainsString('orders', $messages[0]['message']);

        $context = $messages[0]['context'];

        $this->assertSame(CustomerEventsModel::class, $context['model']);
        $this->assertSame('orders', $context['relation']);
        $this->assertSame(1, $context['count']);
        $this->assertIsString($context['trace']);
    }

    public function testModeLogCountsEachLazyLoad(): void
    {
        $guard = new LazyLoadGuard(LazyLoadGuardMode::Log, new SimpleLogger());

        $this->registerGuard(CustomerEventsModel::class, $guard);

        foreach (CustomerEventsModel::query()->all() as $customer) {
            $customer->getOrders();
        }

        $this->assertSame([CustomerEventsModel::class . '::orders' => 3], $guard->getCounters());
    }

    public function testModeStrictThrowsExceptionWithRelationName(): void
    {
        $this->registerGuard(CustomerEventsModel::class, new LazyLoadGuard(LazyLoadGuardMode::Strict));

        $customer = CustomerEventsModel::query()->findByPk(1);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Relation "' . CustomerEventsModel::class . '::orders" is lazy loaded.');

        $customer->getOrders();
    }

    public function testModeStrictCountsLazyLoadBeforeThrowing(): void
    {
        $guard = new LazyLoadGuard(LazyLoadGuardMode::Strict);

        $this->registerGuard(CustomerEventsModel::class, $guard);

        $customer = CustomerEventsModel::query()->findByPk(1);

        try {
            $customer->getOrders();
        } catch (LogicException) {
        }

        $this->assertSame([CustomerEventsModel::class . '::orders' => 1], $guard->getCounters());
    }

    /**
     * @psalm-param class-string $modelClass
     */
    private function registerGuard(string $modelClass, LazyLoadGuard $guard): void
    {
        EventDispatcherProvider::set($modelClass, $this->createDispatcher($guard));
    }

    private function createDispatcher(LazyLoadGuard $guard): EventDispatcherInterface
    {
        return new SimpleEventDispatcher(
            static function (object $event) use ($guard): void {
                if ($event instanceof BeforeLazyRelationLoad) {
                    $guard($event);
                }
            },
        );
    }
}
