<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser;

use PHPUnit\Runner\Extension\ParameterCollection;

/**
 * Files that only call code on behalf of their caller, such as a factory or a dependency injection container.
 * A path matches when it contains one of the fragments.
 *
 * Framework integrations ship their own fragments and merge them with the configured ones.
 */
final readonly class PassThroughPaths
{
    /**
     * @param list<non-empty-string> $fragments
     */
    public function __construct(private array $fragments) {}

    /**
     * Reads the comma-separated `passThroughPaths` parameter of an extension's registration.
     */
    public static function fromParameters(ParameterCollection $parameters): self
    {
        if (!$parameters->has('passThroughPaths')) {
            return new self([]);
        }
        return new self(array_values(array_filter(
            array_map(trim(...), explode(',', $parameters->get('passThroughPaths'))),
            static fn(string $fragment): bool => $fragment !== '',
        )));
    }

    public function merge(self $other): self
    {
        return new self([...$this->fragments, ...$other->fragments]);
    }

    public function isEmpty(): bool
    {
        return $this->fragments === [];
    }

    public function matches(string $file): bool
    {
        $file = str_replace('\\', '/', $file);
        foreach ($this->fragments as $fragment) {
            if (str_contains($file, $fragment)) {
                return true;
            }
        }
        return false;
    }
}
