<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser;

use PHPUnit\TextUI\Configuration\Configuration;
use PHPUnit\TextUI\Configuration\SourceFilter;

/**
 * First-party code as the `<source>` configuration of PHPUnit defines it, for a {@see MessageCauseResolver} that
 * looks for files of the project.
 */
final readonly class FirstPartyCode
{
    /**
     * @var list<string>
     */
    private array $directories;

    /**
     * @param list<string> $directories
     */
    public function __construct(array $directories)
    {
        $this->directories = array_map(static fn(string $directory): string => rtrim($directory, '/'), $directories);
    }

    public static function fromConfiguration(Configuration $configuration): self
    {
        $directories = [];
        foreach ($configuration->source()->includeDirectories() as $directory) {
            $directories[] = $directory->path();
        }
        return new self($directories);
    }

    /**
     * The directories of `<source><include>`; excluded paths and file suffixes still apply, see {@see includes()}.
     *
     * @return list<string>
     */
    public function directories(): array
    {
        return $this->directories;
    }

    public function includes(string $file): bool
    {
        $file = realpath($file);
        return $file !== false && SourceFilter::instance()->includes($file);
    }
}
