<?php

namespace Aybarsm\Laravel\Git;

use Aybarsm\Laravel\Support\Enums\ProcessReturnType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;

class Git
{
    use Macroable;

    public function __construct(
        public readonly string $topLevel
    ) {
    }

    public function getRealPath(string $path = ''): string
    {
        return realpath(pathDir($this->topLevel).(! empty($path) ? pathDir($path) : ''));
    }

    public function run(string $cmd, string $path = '', ProcessReturnType $returnAs = ProcessReturnType::ALL_OUTPUT): mixed
    {
        $cmd = 'git '.trim(ltrim(Str::cleanWhitespace($cmd), 'git'));
        $process = Process::path($this->getRealPath($path))->run($cmd);

        return process_return($process, $returnAs);
    }

    public function isRepoReady(string $path = ''): bool
    {
        return $this->run('rev-parse --is-inside-work-tree', $path, ProcessReturnType::OUTPUT) === 'true';
    }

    public function isDirty(string $path = ''): bool
    {
        return $this->run('diff --quiet || echo "1"', $path, ProcessReturnType::OUTPUT) === '1';
    }

    public function getBranch(string $path = '', ProcessReturnType $returnAs = ProcessReturnType::OUTPUT): mixed
    {
        return $this->run('symbolic-ref --short HEAD', $path, $returnAs);
    }

    public function getTag(string $path = '', ProcessReturnType $returnAs = ProcessReturnType::OUTPUT): mixed
    {
        return $this->run('describe --tags 2>/dev/null', $path, $returnAs);
    }

    public function setTag(string $tag, string $path = '', ProcessReturnType $returnAs = ProcessReturnType::ALL_OUTPUT): mixed
    {
        return $this->run("tag {$tag}", $path, $returnAs);
    }

    public function add(string $path = '', string $args = '. --all', ProcessReturnType $returnAs = ProcessReturnType::ALL_OUTPUT): mixed
    {
        return $this->run("add {$args}", $path, $returnAs);
    }

    public function commit(string $path = '', string $message = '', string $args = '', ProcessReturnType $returnAs = ProcessReturnType::ALL_OUTPUT): mixed
    {
        $cmd = 'commit '.(! empty($message) ? "-m \"{$message}\" " : '').$args;

        return $this->run($cmd, $path, $returnAs);
    }

    public function push(string $path = '', string $remote = 'origin', string $branch = '', string $args = '', ProcessReturnType $returnAs = ProcessReturnType::ALL_OUTPUT): mixed
    {
        $branch = empty($branch) ? $this->getBranch($path) : $branch;

        return $this->run("push {$args} {$remote} {$branch}", $path, $returnAs);
    }

    public function addCommitPush(
        string $path = '',
        string $addArgs = '. --all',
        string $commitMessage = '',
        string $commitArgs = '',
        string $pushRemote = 'origin',
        string $pushBranch = '',
        string $pushArgs = '',
        ProcessReturnType $returnAs = ProcessReturnType::ALL_OUTPUT
    ): object {
        $resultAdd = $this->add($path, $addArgs, ProcessReturnType::INSTANCE);
        if ($resultAdd->failed()) {
            return process_return($resultAdd, $returnAs);
        }

        $resultCommit = $this->commit($path, $commitMessage, $commitArgs, ProcessReturnType::INSTANCE);
        if ($resultCommit->failed()) {
            return process_return($resultCommit, $returnAs);
        }

        $resultPush = $this->push($path, $pushRemote, $pushBranch, $pushArgs, ProcessReturnType::INSTANCE);

        return Arr::toObject([
            'add' => process_return($resultAdd, $returnAs),
            'commit' => process_return($resultCommit, $returnAs),
            'push' => process_return($resultPush, $returnAs),
        ]);
    }

    public function checkout(string $path = '', string $branch = '', string $flags = '', ProcessReturnType $returnAs = ProcessReturnType::ALL_OUTPUT): mixed
    {
        $branch = empty($branch) ? $this->getBranch($path) : $branch;

        return $this->run("checkout {$flags} {$branch}", $path, $returnAs);
    }

    public function getAllSubmodules(): \Illuminate\Support\Collection
    {
        $cmd = "submodule --quiet foreach --recursive '";
        $cmd .= 'echo "NAME[]=$name&SHA1[]=$sha1&PATH[]=$path&BRANCH[]=$(git symbolic-ref --short HEAD)&';
        $cmd .= 'TAG[]=$(git describe --tags 2>/dev/null)&DIRTY[]=$(git diff --quiet || echo "1")"\'';

        $output = trim(Str::replaceLines($this->run($cmd), '&'), '&');

        parse_str($output, $parsed);

        $rtr = Arr::map($parsed['NAME'], function ($val, $key) use ($parsed) {
            return [
                'name' => $val,
                'path' => $parsed['PATH'][$key],
                'branch' => $parsed['BRANCH'][$key],
                'dirty' => boolval($parsed['DIRTY'][$key]),
                'tag' => empty($parsed['TAG'][$key]) ? null : $parsed['TAG'][$key],
                'sha' => $parsed['SHA1'][$key],
            ];
        });

        return collect($rtr);
    }

    /**
     * @throws \Throwable
     */
    private function validateSubmodule(string $path): void
    {
        throw_if(! File::exists($this->getRealPath($path)), \InvalidArgumentException::class, "Submodule path [{$path}] does not exist.");
        throw_if(! $this->isSubmodule($path), \InvalidArgumentException::class, "Path [{$path}] is not a submodule.");
    }

    public function isSubmodule(string $path): bool
    {
        return $this->run('rev-parse --show-superproject-working-tree', $path, ProcessReturnType::OUTPUT) !== '';
    }

    /**
     * @throws \Throwable
     */
    public function updateSubmodule(string $path, string $args = '', ProcessReturnType $returnAs = ProcessReturnType::ALL_OUTPUT): mixed
    {
        $this->validateSubmodule($path);

        return $this->run("submodule update {$args} {$path}", '', $returnAs);
    }

    /**
     * @throws \Throwable
     */
    public function initSubmodule(string $path, string $args = '', ProcessReturnType $returnAs = ProcessReturnType::ALL_OUTPUT): mixed
    {
        $this->validateSubmodule($path);

        return $this->run("submodule init {$args} {$path}", '', $returnAs);
    }

    /**
     * @throws \Throwable
     */
    public function deinitSubmodule(string $path, string $args = '', ProcessReturnType $returnAs = ProcessReturnType::ALL_OUTPUT): mixed
    {
        $this->validateSubmodule($path);

        return $this->run("submodule deinit {$args} {$path}", '', $returnAs);
    }

    /**
     * @throws \Throwable
     */
    public function removeSubmodule(string $path, ProcessReturnType $returnAs = ProcessReturnType::ALL_OUTPUT): mixed
    {
        $this->validateSubmodule($path);

        $resultDeinit = $this->deinitSubmodule($path, '-f', ProcessReturnType::INSTANCE);
        if ($resultDeinit->failed()) {
            return process_return($resultDeinit, $returnAs);
        }

        if (! File::deleteDirectory($this->getRealPath(".git/modules/{$path}"))) {
            return false;
        }

        return $this->run("rm -f {$path}", '', $returnAs);
    }

    /**
     * @throws \Throwable
     */
    // Divide this function as addInitSubmodule
    public function addSubmodule(string $url, string $path, string $args = '', ProcessReturnType $returnAs = ProcessReturnType::ALL_OUTPUT): mixed
    {
        throw_if(! Str::isUrl($url), \InvalidArgumentException::class, "Repo url [{$url}] is invalid.");

        throw_if(File::exists($this->getRealPath($path)), \InvalidArgumentException::class, "Submodule path [{$path}] already exists.");

        return $this->run("submodule add {$args} {$url} {$path}", '', $returnAs);
    }

    /**
     * @throws \Throwable
     */
    public function addInitSubmodule(string $url, string $path, string $args = '', ProcessReturnType $returnAs = ProcessReturnType::ALL_OUTPUT): mixed
    {
        $resultAdd = $this->addSubmodule($url, $path, $args, ProcessReturnType::INSTANCE);
        if ($resultAdd->failed()) {
            return process_return($resultAdd, $returnAs);
        }

        $resultInit = $this->updateSubmodule($path, '--init', ProcessReturnType::INSTANCE);

        return Arr::toObject([
            'add' => process_return($resultAdd, $returnAs),
            'init' => process_return($resultInit, $returnAs),
        ]);
    }
}
