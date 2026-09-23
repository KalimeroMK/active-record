<?php

declare(strict_types=1);

namespace Yiisoft\ActiveRecord\Event\Guard;

/**
 * Modes of the {@see LazyLoadGuard} listener.
 */
enum LazyLoadGuardMode
{
    /** Every lazy load is reported as a PSR-3 warning. */
    case Log;
    /** Every lazy load throws a `LogicException`. */
    case Strict;
}
