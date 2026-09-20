<?php

declare(strict_types=1);

namespace Yiisoft\ActiveRecord\Tests\Stubs\ActiveRecord;

use Yiisoft\ActiveRecord\Trait\EventsTrait;

final class OrderEventsModel extends Order
{
    use EventsTrait;
}
