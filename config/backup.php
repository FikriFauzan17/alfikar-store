<?php

return [
    'path' => env(
        'BACKUP_PATH',
        dirname(base_path()).DIRECTORY_SEPARATOR.basename(base_path()).'-backups',
    ),
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),
    'image_disk' => env('BACKUP_IMAGE_DISK', env('PRODUCT_IMAGE_DISK', 'public')),
];
