<?php

namespace Aybarsm\Laravel\Git;

use Aybarsm\Laravel\Git\Contracts\GitInterface;
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
        protected $repoProvider,
        protected array $repoList
    ) {

    }

    /**
     * @throws \Throwable
     */
    public function run(string $path, string $command, string|array $args, string $subCommand = null, ProcessReturnType $returnAs = ProcessReturnType::SUCCESSFUL): bool|object|string
    {
        $command = str($command)->squish()->kebab()->value();

        if ($subCommand !== null) {
            $subCommand = Str::squish($subCommand);
            $subCommands = config("git.commands.{$command}.subcommands", []);
            $prefixes = config("git.commands.{$command}.subcommand_prefixes", []);
            $available = count($prefixes) ? Arr::map(Arr::crossJoin($prefixes, $subCommands), fn ($val, $key) => Str::squish(Arr::join($val, ' '))) : $subCommands;

            throw_if(
                ! in_array($subCommand, $available),
                \InvalidArgumentException::class,
                "Subcommand [{$subCommand}] is not in command [{$command}] available list."
            );

            $command .= " {$subCommand}";
        }

        $path = realpath(pathDir($path));
        $args = $this->buildArgs($args);
        $command = str("{$command} {$args}")->start('git ')->value();

        return process_return(Process::path($path)->run($command), $returnAs);
    }

    public function buildArgs(string|array $args): string
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

    /**
     * @throws \Throwable
     */
    public function addRepo(string $name, string $path, bool $replace = false): static
    {
        throw_if(Arr::exists(static::$repos, $name) && $replace === false, \InvalidArgumentException::class, "Repo [{$name}] already exists.");
        throw_if(! File::isDirectory($path), \InvalidArgumentException::class, "Path [{$path}] is not a directory.");

        static::$repos[$name] = new $this->repoProvider($name, realpath($path));

        return $this;
    }

    public function repo(string $name = 'default')
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

    public function getRepoProvider(): mixed
    {
        return $this->repoProvider;
    }

    /**
     * @throws \Throwable
     */
    public function clone(string $repoUrl, string $path, array|string $args, string $name = ''): static
    {
        throw_if(! Str::isUrl($repoUrl), \InvalidArgumentException::class, "Url [{$repoUrl}] is invalid");
        $path = realpath($path);
        throw_if(! File::isDirectory($baseDir = realpath("{$path}/..")), \InvalidArgumentException::class, "Path [{$baseDir}] is not a directory.");
        throw_if(File::exists($path), \InvalidArgumentException::class, "Path [{$path}] already exists.");

        $args = Str::squish(is_array($args) ? $this->buildArgs($args) : $args." {$repoUrl} {$path}");

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
