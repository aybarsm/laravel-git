<?php

namespace Aybarsm\Laravel\Git\Contracts;

use Aybarsm\Laravel\Support\Enums\ProcessReturnType;
use Illuminate\Support\Collection;

interface GitInterface
{
    public function run(string $path, string $command, string|array $args, string $subCommand = null, ProcessReturnType $returnAs = ProcessReturnType::SUCCESSFUL): mixed;

    public function buildArgs(string|array $args): string;

    public function addRepo(string $name, string $path, bool $replace = false): static;

    public function repo(string $name = 'default');

    public function repos(): Collection;

    public function getRepoProvider(): mixed;

    public function clone(string $repoUrl, string $path, array|string $args): mixed;

    public function help(array|string $args = ''): string;

    public function version(array|string $args = ''): string;
}
