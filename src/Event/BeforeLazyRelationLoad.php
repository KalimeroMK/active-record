<?php

declare(strict_types=1);

namespace Yiisoft\ActiveRecord\Event;

use Yiisoft\ActiveRecord\ActiveRecordInterface;

/**
 * Event triggered before a relation is lazy-loaded, that is, accessed without having been eager-loaded beforehand.
 *
 * @see ActiveRecordInterface::relation()
 */
final class BeforeLazyRelationLoad extends AbstractEvent
{
    /**
     * @param ActiveRecordInterface $model The model whose relation is being lazy-loaded.
     * @param string $relationName The name of the relation being lazy-loaded.
     */
    public function __construct(ActiveRecordInterface $model, public readonly string $relationName)
    {
        parent::__construct($model);
    }
}
