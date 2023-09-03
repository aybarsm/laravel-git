<?php

namespace Aybarsm\Laravel\Git;

use Aybarsm\Laravel\Support\Enums\ProcessReturnType;
use Illuminate\Contracts\Process\ProcessResult;
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

    public function run(string $cmd, string $path = '', ProcessReturnType $returnAs = ProcessReturnType::OUTPUT): bool|string|ProcessResult
    {
        $cmd = 'git '.trim(ltrim(Str::cleanWhitespace($cmd), 'git'));
        $process = Process::path($this->getRealPath($path))->run($cmd);

        return process_return($process, $returnAs);
    }

    public function isRepoReady(string $path = ''): bool
    {
        return $this->run('rev-parse --is-inside-work-tree', $path) === 'true';
    }

    public function isDirty(string $path = ''): bool
    {
        return $this->run('diff --quiet || echo "1"', $path) === '1';
    }

    public function getBranch(string $path = '', ProcessReturnType $returnAs = ProcessReturnType::OUTPUT): bool|string|ProcessResult
    {
        return $this->run('symbolic-ref --short HEAD', $path, $returnAs);
    }

    public function getTag(string $path = '', ProcessReturnType $returnAs = ProcessReturnType::OUTPUT): bool|string|ProcessResult
    {
        return $this->run('describe --tags 2>/dev/null', $path, $returnAs);
    }

    public function setTag(string $tag, string $path = '', ProcessReturnType $returnAs = ProcessReturnType::OUTPUT): bool|string|ProcessResult
    {
        return $this->run("tag {$tag}", $path, $returnAs);
    }

    public function push(string $path = '', string $remote = 'origin', string $branch = '', ProcessReturnType $returnAs = ProcessReturnType::OUTPUT): bool|string|ProcessResult
    {
        $branch = empty($branch) ? $this->getBranch($path) : $branch;

        return $this->run("push {$remote} {$branch}", $path, $returnAs);
    }

    public function add(string $path = '', string $args = '. --all', ProcessReturnType $returnAs = ProcessReturnType::OUTPUT): bool|string|ProcessResult
    {
        return $this->run("add {$args}", $path, $returnAs);
    }

    public function commit(string $path = '', string $message = '', string $more = '', ProcessReturnType $returnAs = ProcessReturnType::OUTPUT): bool|string|ProcessResult
    {
        $cmd = 'commit '.(! empty($message) ? "-m \"{$message}\" " : '').$more;

        return $this->run($cmd, $path, $returnAs);
    }

    public function checkout(string $path = '', string $branch = '', string $flags = '', ProcessReturnType $returnAs = ProcessReturnType::OUTPUT): bool|string|ProcessResult
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
        return $this->run('rev-parse --show-superproject-working-tree', $path) !== '';
    }

    /**
     * @throws \Throwable
     */
    public function updateSubmodule(string $path, string $args = '', ProcessReturnType $returnAs = ProcessReturnType::OUTPUT): bool|string|ProcessResult
    {
        $this->validateSubmodule($path);

        return $this->run("submodule update {$args} {$path}", '', $returnAs);
    }

    /**
     * @throws \Throwable
     */
    public function initSubmodule(string $path, string $args = '', ProcessReturnType $returnAs = ProcessReturnType::OUTPUT): bool|string|ProcessResult
    {
        $this->validateSubmodule($path);

        return $this->run("submodule init {$args} {$path}", '', $returnAs);
    }

    /**
     * @throws \Throwable
     */
    public function deinitSubmodule(string $path, string $args = '', ProcessReturnType $returnAs = ProcessReturnType::OUTPUT): bool|string|ProcessResult
    {
        $this->validateSubmodule($path);

        return $this->run("submodule deinit {$args} {$path}", '', $returnAs);
    }

    /**
     * @throws \Throwable
     */
    public function removeSubmodule(string $path, ProcessReturnType $returnAs = ProcessReturnType::OUTPUT): bool|string|ProcessResult
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
    public function addSubmodule(string $url, string $path, string $args = '', bool $initSuccessful = true, ProcessReturnType $returnAs = ProcessReturnType::OUTPUT): bool|string|ProcessResult
    {
        throw_if(! Str::isUrl($url), \InvalidArgumentException::class, "Repo url [{$url}] is invalid.");

        throw_if(File::exists($this->getRealPath($path)), \InvalidArgumentException::class, "Submodule path [{$path}] already exists.");

        $resultAdd = $this->run("submodule add {$args} {$url} {$path}", '', $initSuccessful ? ProcessReturnType::SUCCESSFUL : $returnAs);

        if (! $initSuccessful || $resultAdd === false) {
            return $resultAdd;
        }

        return $this->updateSubmodule($path, '--init', $returnAs);
    }
}
