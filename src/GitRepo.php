<?php

namespace Aybarsm\Laravel\Git;

use Aybarsm\Laravel\Git\Contracts\GitRepoInterface;
use Aybarsm\Laravel\Support\Enums\ProcessReturnType;
use Aybarsm\Laravel\Support\Enums\StrLinesAction;
use Illuminate\Process\ProcessResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Symfony\Component\VarDumper\VarDumper;

class GitRepo implements GitRepoInterface
{
    use Conditionable, Macroable;

    protected static Collection $submodules;

    protected ProcessResult $processResult;

    public function __construct(
        public readonly string $name,
        public readonly string $path
    ) {
        static::$submodules = collect();
    }

    public static function make(string $name, string $path): static
    {
        return new static($name, $path);
    }

    public function dump(): static
    {
        VarDumper::dump($this->processResult);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function dd()
    {
        $this->dump();

        exit(1);
    }

    public function result($returnAs = ProcessReturnType::INSTANCE): bool|object|string
    {
        return process_return($this->processResult, $returnAs);
    }

    public function whenSuccessful(callable $callback, callable $default = null): static
    {
        return $this->when(
            fn (): bool => $this->result(ProcessReturnType::SUCCESSFUL),
            fn (): static => call_user_func($callback, $this),
            fn (): static => call_user_func($default, $this),
        );
    }

    public function whenFailed(callable $callback, callable $default = null): static
    {
        return $this->when(
            fn (): bool => $this->result(ProcessReturnType::FAILED),
            fn (): static => call_user_func($callback, $this),
            fn (): static => call_user_func($default, $this),
        );
    }

    public function isReady(): bool
    {
        return $this->revParse('--is-inside-work-tree')->result(ProcessReturnType::OUTPUT) === 'true';
    }

    public function isDirty(): bool
    {
        return $this->diff('--stat')->result(ProcessReturnType::OUTPUT) !== '';
    }

    public function getBranch(): string
    {
        return $this->symbolicRef('--short HEAD')->result(ProcessReturnType::OUTPUT);
    }

    public function getTag(): ?string
    {
        $tag = $this->describe('--tags')->result(ProcessReturnType::OUTPUT);

        return empty($tag) ? null : $tag;
    }

    public function getTags(): Collection
    {
        return str($this->tag()->result(ProcessReturnType::OUTPUT))->lines(StrLinesAction::SPLIT, -1, PREG_SPLIT_NO_EMPTY);
    }

    public function searchSub(string $search): Collection
    {
        if (static::$submodules->isEmpty() && ! ($scanned = $this->scanSubmodules())->isEmpty()) {
            $this->buildSubmodules($scanned);
            if (static::$submodules->isEmpty()) {
                return collect();
            }
        }

        return static::$submodules->filter(fn ($item) => Str::contains($item->name, $search, true) || Str::contains($item->path, $search, true));
    }

    public function subs(string $search = ''): Collection
    {
        return empty($search) ? static::$submodules : $this->searchSub($search);
    }

    public function sub(string $search): ?static
    {
        return $this->searchSub($search)?->first();
    }

    public function buildSubmodules(Collection $submodules = null): static
    {
        $submodules = $submodules ?: $this->scanSubmodules();

        if ($submodules->isEmpty()) {
            return $this;
        }

        static::$submodules = collect();

        $submodules->each(function ($item, $key) {
            if (File::isDirectory($item->path)) {
                static::$submodules = static::$submodules->add(new self($item->name, $item->path));
            }
        });

        return $this;
    }

    public function scanSubmodules(): Collection
    {
        $args = '\'echo "{\"name\":\"$name\",\"path\":\"$toplevel/$displaypath\"},"\'';

        if ($this->submodule('--quiet foreach', $args)->result(ProcessReturnType::FAILED)) {
            return collect();
        }

        $output = str($this->result(ProcessReturnType::OUTPUT))->squish()->trim(',')->start('[')->finish(']')->value();

        return collect(Str::isJson($output) ? json_decode($output) : []);

        dump($output);
        dump(Str::isJson($output));

        return collect();
        $subs = str($output)
            ->lines(StrLinesAction::SPLIT, -1, PREG_SPLIT_NO_EMPTY)
            ->whenNotEmpty(
                fn (Collection $collection): Collection => $collection->transform(
                    fn ($item, $key): ?object => Str::isJson($item) ? json_decode($item) : null
                )
            )
            ->filter();

        //        dump($subs);
        //        return $subs;
        return collect();
    }

    public function forward(string $function, array|string $args = '', string $subCommand = null): static
    {
        $this->processResult = app('git')->run($this->path, $function, $args, $subCommand, ProcessReturnType::INSTANCE);

        return $this;
    }

    public function am(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function add(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function archive(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function bisect(string $subCommand, array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $subCommand, $args);
    }

    public function branch(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function bundle(string $subCommand, array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args, $subCommand);
    }

    public function checkout(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function cherryPick(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function clean(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function commit(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function describe(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function diff(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function fetch(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function formatPatch(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function gc(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function grep(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function init(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function log(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function maintenance(string $subCommand, array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args, $subCommand);
    }

    public function merge(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function mv(string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function notes(string $subCommand, array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args, $subCommand);
    }

    public function pull(array|string $args): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function push(array|string $args): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function rangeDiff(array|string $args): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function rebase(array|string $args): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function reset(array|string $args): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function restore(array|string $args): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function revert(array|string $args): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function rm(array|string $args): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function shortLog(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function show(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function sparseCheckout(string $subCommand, array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args, $subCommand);
    }

    public function stash(string $subCommand, array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args, $subCommand);
    }

    public function status(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function submodule(string $subCommand, array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args, $subCommand);
    }

    public function switch(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function tag(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function whatchanged(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function worktree(string $subCommand, array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args, $subCommand);
    }

    public function config(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function fastExport(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function fastImport(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function filterBranch(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function mergetool(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function packRefs(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function prune(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function reflog(string $subCommand, array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args, $subCommand);
    }

    public function remote(string $subCommand, array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args, $subCommand);
    }

    public function repack(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function replace(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function annotate(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function blame(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function bugreport(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function countObjects(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function diagnose(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function difftool(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function fsck(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function mergeTree(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function rerere(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function showBranch(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function verifyCommit(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function verifyTag(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function archimport(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function cvsexportcommit(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function cvsimport(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function imapSend(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function p4(string $subCommand, array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args, $subCommand);
    }

    public function quiltimport(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function requestPull(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function sendEmail(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function svn(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function apply(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function checkoutIndex(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function commitGraph(string $subCommand, array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args, $subCommand);
    }

    public function commitTree(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function hashObject(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function indexPack(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function mergeFile(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function mergeIndex(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function mktag(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function mktree(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function multiPackIndex(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function packObjects(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function prunePacked(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function readTree(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function symbolicRef(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function unpackObjects(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function updateIndex(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function updateRef(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function writeTree(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function catFile(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function cherry(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function diffFiles(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function diffIndex(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function diffTree(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function forEachRef(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function forEachRepo(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function getTarCommitId(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function lsFiles(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function lsRemote(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function lsTree(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function mergeBase(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function nameRev(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function packRedundant(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function revList(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function revParse(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function showIndex(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function showRef(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function unpackFile(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function var(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function verifyPack(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function fetchPack(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function sendPack(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function httpFetch(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function httpPush(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function receivePack(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function uploadArchive(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function checkAttr(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function checkIgnore(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function checkMailmap(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function checkRefFormat(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function column(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function credential(string $subCommand, array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args, $subCommand);
    }

    public function fmtMergeMsg(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function hook(string $subCommand, array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args, $subCommand);
    }

    public function interpretTrailers(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function maininfo(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function mailsplit(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function mergeOneFile(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function patchId(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }

    public function stripspace(array|string $args = ''): static
    {
        return $this->forward(__FUNCTION__, $args);
    }
}
