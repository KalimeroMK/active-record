<?php

declare(strict_types=1);

namespace Yiisoft\ActiveRecord\Event\Guard;

use Exception;
use LogicException;
use Psr\Log\LoggerInterface;
use Yiisoft\ActiveRecord\Event\BeforeLazyRelationLoad;

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
     */
    public function __construct(
        private readonly LazyLoadGuardMode $mode = LazyLoadGuardMode::Log,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function __invoke(BeforeLazyRelationLoad $event): void
    {
        $modelClass = $event->model::class;
        $relation = $modelClass . '::' . $event->relationName;

        $this->counters[$relation] = ($this->counters[$relation] ?? 0) + 1;

        if ($this->mode === LazyLoadGuardMode::Strict) {
            throw new LogicException("Relation \"$relation\" is lazy loaded.");
        }

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
}
