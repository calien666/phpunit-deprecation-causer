<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser;

/**
 * Names the cause of a deprecation that a framework triggers about project configuration, where the stack names no
 * frame of the project. Framework integrations pass their resolvers to {@see DeprecationCauserRegistrar};
 * {@see FirstPartyCode} tells which files belong to the project.
 *
 * @phpstan-import-type StackFrame from CausingFileLocator
 */
interface CauseResolver
{
    /**
     * Returns null for a deprecation the resolver does not recognise, the stack decides then. For a recognised one
     * its answer wins over the stack.
     *
     * @param list<StackFrame> $trace Frame 0 holds the file that triggered the deprecation, frames carry their
     *                                arguments
     */
    public function cause(array $trace, string $message): ?Cause;
}
