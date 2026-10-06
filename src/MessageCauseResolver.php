<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser;

/**
 * Names the file that caused a deprecation from its message alone, for deprecations a framework triggers about
 * project configuration, where no frame of the project is on the stack. Framework integrations pass their resolvers
 * to {@see DeprecationCauserRegistrar}; {@see FirstPartyCode} tells which files belong to the project.
 */
interface MessageCauseResolver
{
    /**
     * Returns null when the message names nothing that first-party code caused.
     *
     * @return non-empty-string|null
     */
    public function causingFile(string $message): ?string;
}
