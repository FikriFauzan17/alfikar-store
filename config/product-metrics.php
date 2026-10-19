<?php

return [
    'retention_days' => (int) env('PRODUCT_METRICS_RETENTION_DAYS', 365),
    'deduplicate_minutes' => (int) env('PRODUCT_METRICS_DEDUPLICATE_MINUTES', 30),
];
