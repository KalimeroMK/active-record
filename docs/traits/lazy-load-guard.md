# LazyLoadGuardTrait

`LazyLoadGuardTrait` allows detecting N+1 queries caused by lazy loading of relations, i.e. reading a relation
that wasn't eager-loaded by `with()`.

```php
use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\ActiveRecord\Trait\LazyLoadGuardTrait;

final class Customer extends ActiveRecord
{
    use LazyLoadGuardTrait;
}
```

Every lazy load is registered by [LazyLoadGuard](../../src/LazyLoadGuard.php) and reported according to its mode:

```php
use Yiisoft\ActiveRecord\LazyLoadGuard;
use Yiisoft\ActiveRecord\LazyLoadGuardMode;

LazyLoadGuard::set(LazyLoadGuardMode::Log, $logger);
```

| Mode     | Behavior                                                                                          |
|----------|---------------------------------------------------------------------------------------------------|
| `Log`    | Default. Reports a PSR-3 warning with the relation name, the per-request count and a stack trace |
| `Strict` | Throws `LogicException`                                                                           |

In `Log` mode the logger is optional; when it's omitted the lazy loads are still counted and readable through
`LazyLoadGuard::getCounters()`, but nothing is written anywhere. `LazyLoadGuard::reset()` restores the defaults
and clears the counters.

The guard is meant for development and testing, e.g. `Strict` in the test suite and `Log` on staging.

> [!NOTE]
> The trait overrides `retrieveRelation()` method. If another trait of the model overrides it too,
> resolve the conflict using `insteadof` and `as` operators.

Back to [Extending Functionality With Traits](traits.md).
