<?php

namespace Aybarsm\Laravel\Git;

use Aybarsm\Laravel\Support\Enums\ProcessReturnType;
use Illuminate\Process\ProcessResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;

class Git
{
    use Macroable;

    protected static array $repos = [];

    protected ProcessResult $processResult;

    public function __construct(
        protected $repoProvider,
        protected array $repoList
    ) {

    }

    public function run(string $path, string $command, string|array $args, string $subCommand = null, ProcessReturnType $returnAs = ProcessReturnType::SUCCESSFUL): mixed
    {
        $command = trim(ltrim(Str::cleanWhitespace(Str::kebab($command)), 'git'));

        if ($subCommand !== null) {
            $subCommand = trim(Str::cleanWhitespace($subCommand));
            $subCommands = config("git.commands.{$command}.subcommands", []);
            $prefixes = config("git.commands.{$command}.subcommand_prefixes", []);
            $available = count($prefixes) ? Arr::map(Arr::crossJoin($prefixes, $subCommands), fn ($val, $key) => Str::cleanWhitespace(Arr::join($val, ' '))) : $subCommands;

            throw_if(
                ! in_array($subCommand, $available),
                \InvalidArgumentException::class,
                "Subcommand [{$subCommand}] is not in command [{$command}] available list."
            );

            $command .= " {$subCommand}";
        }

        $path = realpath(pathDir($path));
        $command = "git {$command}";
        $args = $this->buildArgs($args);

        $result = Process::path($path)->run("{$command} {$args}");

        return process_return($result, $returnAs);
    }

    protected function buildArgs(string|array $args): string
    {
        if (is_string($args)) {
            return Str::cleanWhitespace($args);
        }

        $built = [];
        foreach ($args as $arg => $val) {
            $built[] = match (true) {
                Str::startsWith($arg, '--') => $arg.(! empty($val) ? "={$val}" : ''),
                Str::startsWith($arg, '-') => $arg.(! empty($val) ? " {$val}" : ''),
                default => $val
            };
        }

        return Str::cleanWhitespace(implode(' ', $built));
    }

    /**
     * @throws \Throwable
     */
    public function addRepo(string $name, string $path, bool $replace = false): static
    {
        throw_if(Arr::exists(static::$repos, $name) && $replace === false, \InvalidArgumentException::class, "Repo [{$name}] already exists.");
        throw_if(! File::isDirectory($relPath = realpath("{$path}/..")), \InvalidArgumentException::class, "Path [{$relPath}] is not a directory.");

        static::$repos[$name] = new $this->repoProvider($name, realpath($path));

        return $this;
    }

    public function reLoadRepos(): void
    {
        foreach ($this->repoList as $name => $path) {
            $this->addRepo($name, $path, true);
            $this->repo($name)->buildSubmodules();
        }
    }

    public function repo(string $name = 'default')
    {
        return static::$repos[$name] ?? null;
    }

    public function getRepoProvider(): mixed
    {
        return $this->repoProvider;
    }

    /**
     * @throws \Throwable
     */
    public function clone(string $repoUrl, string $path, array|string $args): mixed
    {
        throw_if(! Str::isUrl($repoUrl), \InvalidArgumentException::class, "Url [{$repoUrl}] is invalid");
        $path = realpath($path);
        throw_if(! File::isDirectory($baseDir = realpath("{$path}/..")), \InvalidArgumentException::class, "Path [{$baseDir}] is not a directory.");
        throw_if(File::exists($path), \InvalidArgumentException::class, "Path [{$path}] already exists.");

        $args = is_array($args) ? array_merge($args, [$repoUrl, $path]) : "{$args} {$repoUrl} {$path}";

        return $this->run(base_path(), 'clone', $args) ? new $this->repoProvider($path, $path) : null;
    }

    public function help(array|string $args = ''): string
    {
        $this->run(base_path(), 'help', $args, null, ProcessReturnType::OUTPUT);
    }

    public function version(array|string $args = ''): string
    {
        $this->run(base_path(), 'version', $args, null, ProcessReturnType::OUTPUT);
    }
}
