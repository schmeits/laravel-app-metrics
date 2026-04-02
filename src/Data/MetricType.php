<?php

declare(strict_types=1);

/**
 * src/Data/MetricType.php
 *
 * Enum defining the supported metric value types.
 */

namespace Schmeits\AppMetrics\Data;

enum MetricType: string
{
    case Numeric = 'numeric';
    case Currency = 'currency';
    case Percentage = 'percentage';
    case String = 'string';
}
