<?php

declare(strict_types=1);

namespace Yiisoft\ActiveRecord\Event\Guard;

use Exception;
use LogicException;
use Psr\Log\LoggerInterface;
use Yiisoft\ActiveRecord\Event\BeforeLazyRelationLoad;

use function is_a;

/**
 * Listener of the {@see BeforeLazyRelationLoad} event which detects N+1 queries caused by lazy loading of relations.
 */
final class LazyLoadGuard
{
    /**
     * @var int[] Number of detected lazy loads `[model_class::relation_name => count, ...]`
     */
    private array $counters = [];

    /**
     * @param LazyLoadGuardMode $mode The mode of the guard.
     * @param LoggerInterface|null $logger The logger used in the {@see LazyLoadGuardMode::Log} mode.
     * @param string[] $only Model classes to guard, all classes are guarded if empty.
     * @param string[] $except Model classes to skip.
     *
     * @psalm-param list<class-string> $only
     * @psalm-param list<class-string> $except
     */
    public function __construct(
        private readonly LazyLoadGuardMode $mode = LazyLoadGuardMode::Off,
        private readonly ?LoggerInterface $logger = null,
        private readonly array $only = [],
        private readonly array $except = [],
    ) {}

    public function __invoke(BeforeLazyRelationLoad $event): void
    {
        $modelClass = $event->model::class;

        if ($this->mode === LazyLoadGuardMode::Off || !$this->isGuarded($modelClass)) {
            return;
        }

        $relation = $modelClass . '::' . $event->relationName;

        if ($this->mode === LazyLoadGuardMode::Strict) {
            throw new LogicException("Relation \"$relation\" is lazy loaded.");
        }

        $this->counters[$relation] = ($this->counters[$relation] ?? 0) + 1;

        $this->logger?->warning(
            "Relation \"$relation\" is lazy loaded.",
            [
                'model' => $modelClass,
                'relation' => $event->relationName,
                'count' => $this->counters[$relation],
                'trace' => (new Exception())->getTraceAsString(),
            ],
        );
    }

    /**
     * Returns the number of detected lazy loads `[model_class::relation_name => count, ...]`.
     *
     * @return int[]
     */
    public function getCounters(): array
    {
        return $this->counters;
    }

    /**
     * @psalm-param class-string $modelClass
     */
    private function isGuarded(string $modelClass): bool
    {
        foreach ($this->except as $class) {
            if (is_a($modelClass, $class, true)) {
                return false;
            }
        }

        if ($this->only === []) {
            return true;
        }

        foreach ($this->only as $class) {
            if (is_a($modelClass, $class, true)) {
                return true;
            }
        }

        return false;
    }
}
