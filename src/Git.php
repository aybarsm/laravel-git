<?php

namespace Aybarsm\Laravel\Git;

use Aybarsm\Laravel\Git\Contracts\GitInterface;
use Aybarsm\Laravel\Git\Contracts\GitRepoInterface;
use Aybarsm\Laravel\Support\Enums\ProcessReturnType;
use Illuminate\Process\ProcessResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;

class Git implements GitInterface
{
    use Conditionable, Macroable;

    protected static array $repos = [];

    protected ProcessResult $processResult;

    public function __construct(
        protected array $repoList
    ) {

    }

    public function run(string $path, string $command, string|array $args, string $subCommand = null, ProcessReturnType $returnAs = ProcessReturnType::SUCCESSFUL): bool|object|string
    {
        $command = str($command)->squish()->kebab()->value();

        if ($subCommand !== null) {
            $subCommand = Str::squish($subCommand);
            $subCommands = sconfig("git.commands.{$command}.subcommands", []);
            $prefixes = sconfig("git.commands.{$command}.subcommand_prefixes", []);
            $available = count($prefixes) ? Arr::map(Arr::crossJoin($prefixes, $subCommands), fn ($val, $key) => Str::squish(Arr::join($val, ' '))) : $subCommands;

            if (! in_array($subCommand, $available)) {
                throw new \InvalidArgumentException("Subcommand [{$subCommand}] is not in command [{$command}] available list.");
            }

            $command .= " {$subCommand}";
        }

        $path = realpath(pathDir($path));
        $args = $this->buildArgs($args);
        $command = str("{$command} {$args}")->start('git ')->value();

        return process_return(Process::path($path)->run($command), $returnAs);
    }

    protected function buildArgs(string|array $args): string
    {
        if (is_string($args)) {
            return Str::squish($args);
        }

        $built = [];
        foreach ($args as $arg => $val) {
            $built[] = match (true) {
                Str::startsWith($arg, '--') => $arg.(! empty($val) ? "={$val}" : ''),
                Str::startsWith($arg, '-') => $arg.(! empty($val) ? " {$val}" : ''),
                default => $val
            };
        }

        return Str::squish(implode(' ', $built));
    }

    public function addRepo(string $name, string $path, bool $replace = false): static
    {
        if (Arr::exists(static::$repos, $name) && $replace === false) {
            throw new \InvalidArgumentException("Repo [{$name}] already exists.");
        } elseif (! File::isDirectory($path)) {
            throw new \InvalidArgumentException("Path [{$path}] is not a directory.");
        }

        static::$repos[$name] = app()->make(GitRepoInterface::class, ['name' => $name, 'path' => realpath($path)]);

        return $this;
    }

    public function repo(string $name = 'default'): ?GitRepoInterface
    {
        if (! empty($this->repoList) && Arr::exists($this->repoList, $name)) {
            $this->addRepo($name, $this->repoList[$name], true);
        }

        return static::$repos[$name] ?? null;
    }

    public function repos(): Collection
    {
        return collect(static::$repos);
    }

    public function clone(string $repoUrl, string $path, array|string $args, string $name = ''): static
    {
        $path = realpath($path);
        if (! Str::isUrl($repoUrl)) {
            throw new \InvalidArgumentException("Url [{$repoUrl}] is invalid");
        } elseif (! File::isDirectory($baseDir = realpath("{$path}/.."))) {
            throw new \InvalidArgumentException("Path [{$baseDir}] is not a directory.");
        } elseif (File::exists($path)) {
            throw new \InvalidArgumentException("Path [{$baseDir}] is not a directory.");
        }

        $args = $this->buildArgs($args)." {$repoUrl} {$path}";

        $this->processResult = $this->run(base_path(), __FUNCTION__, $args);

        if ($this->processResult->successful()) {
            $this->addRepo(empty($name) ? $path : $name, $path);
        }

        return $this;
    }

    public function help(array|string $args = ''): string
    {
        $this->processResult = $this->run(base_path(), __FUNCTION__, $args);

        return process_return($this->processResult, ProcessReturnType::OUTPUT);
    }

    public function version(array|string $args = ''): string
    {
        $this->processResult = $this->run(base_path(), __FUNCTION__, $args);

        return process_return($this->processResult, ProcessReturnType::OUTPUT);
    }
}
