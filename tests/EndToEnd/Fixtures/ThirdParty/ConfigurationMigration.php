<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\ThirdParty;

/**
 * Stands in for a framework migrating configuration at runtime: no frame of the code that owns the configuration is
 * on the stack, only the message names it.
 */
final class ConfigurationMigration
{
    public function migrate(string $item): void
    {
        trigger_error(sprintf('Migrated the deprecated option of configuration item \'%s\'.', $item), E_USER_DEPRECATED);
    }

    /**
     * Names the item only in its argument, not in the message.
     */
    public function migrateUnnamed(string $item): void
    {
        if ($item !== '') {
            trigger_error('Migrated the deprecated option of a configuration item.', E_USER_DEPRECATED);
        }
    }
}
