<?php

namespace Aybarsm\Laravel\Git\Contracts;

use Illuminate\Support\Collection;

interface GitRepoInterface
{
    public static function make(string $name, string $path): static;

    public function dump(): static;

    /**
     * Dump the string and end the script.
     *
     * @return never
     */
    public function dd();

    public function result(): bool|object|string;

    public function whenSuccessful(callable $callback, callable $default = null): static;

    public function whenFailed(callable $callback, callable $default = null): static;

    public function searchSub(string $search): Collection;

    public function subs(string $search = ''): Collection;

    public function sub(string $search): ?static;

    public function isReady(): bool;

    public function isDirty(): bool;

    public function getBranch(): string;

    public function getTag(): ?string;

    public function getTags(): Collection;

    public function buildSubmodules(Collection $submodules = null): static;

    public function scanSubmodules(): Collection;

    public function forward(string $function, array|string $args = '', string $subCommand = null): static;

    public function am(array|string $args = ''): static;

    public function add(array|string $args = ''): static;

    public function archive(array|string $args = ''): static;

    public function bisect(string $subCommand, array|string $args = ''): static;

    public function branch(array|string $args = ''): static;

    public function bundle(string $subCommand, array|string $args = ''): static;

    public function checkout(array|string $args = ''): static;

    public function cherryPick(array|string $args = ''): static;

    public function clean(array|string $args = ''): static;

    public function commit(array|string $args = ''): static;

    public function describe(array|string $args = ''): static;

    public function diff(array|string $args = ''): static;

    public function fetch(array|string $args = ''): static;

    public function formatPatch(array|string $args = ''): static;

    public function gc(array|string $args = ''): static;

    public function grep(array|string $args = ''): static;

    public function init(array|string $args = ''): static;

    public function log(array|string $args = ''): static;

    public function maintenance(string $subCommand, array|string $args = ''): static;

    public function merge(array|string $args = ''): static;

    public function mv(string $args = ''): static;

    public function notes(string $subCommand, array|string $args = ''): static;

    public function pull(array|string $args): static;

    public function push(array|string $args): static;

    public function rangeDiff(array|string $args): static;

    public function rebase(array|string $args): static;

    public function reset(array|string $args): static;

    public function restore(array|string $args): static;

    public function revert(array|string $args): static;

    public function rm(array|string $args): static;

    public function shortLog(array|string $args = ''): static;

    public function show(array|string $args = ''): static;

    public function sparseCheckout(string $subCommand, array|string $args = ''): static;

    public function stash(string $subCommand, array|string $args = ''): static;

    public function status(array|string $args = ''): static;

    public function submodule(string $subCommand, array|string $args = ''): static;

    public function switch(array|string $args = ''): static;

    public function tag(array|string $args = ''): static;

    public function whatchanged(array|string $args = ''): static;

    public function worktree(string $subCommand, array|string $args = ''): static;

    public function config(array|string $args = ''): static;

    public function fastExport(array|string $args = ''): static;

    public function fastImport(array|string $args = ''): static;

    public function filterBranch(array|string $args = ''): static;

    public function mergetool(array|string $args = ''): static;

    public function packRefs(array|string $args = ''): static;

    public function prune(array|string $args = ''): static;

    public function reflog(string $subCommand, array|string $args = ''): static;

    public function remote(string $subCommand, array|string $args = ''): static;

    public function repack(array|string $args = ''): static;

    public function replace(array|string $args = ''): static;

    public function annotate(array|string $args = ''): static;

    public function blame(array|string $args = ''): static;

    public function bugreport(array|string $args = ''): static;

    public function countObjects(array|string $args = ''): static;

    public function diagnose(array|string $args = ''): static;

    public function difftool(array|string $args = ''): static;

    public function fsck(array|string $args = ''): static;

    public function mergeTree(array|string $args = ''): static;

    public function rerere(array|string $args = ''): static;

    public function showBranch(array|string $args = ''): static;

    public function verifyCommit(array|string $args = ''): static;

    public function verifyTag(array|string $args = ''): static;

    public function archimport(array|string $args = ''): static;

    public function cvsexportcommit(array|string $args = ''): static;

    public function cvsimport(array|string $args = ''): static;

    public function imapSend(array|string $args = ''): static;

    public function p4(string $subCommand, array|string $args = ''): static;

    public function quiltimport(array|string $args = ''): static;

    public function requestPull(array|string $args = ''): static;

    public function sendEmail(array|string $args = ''): static;

    public function svn(array|string $args = ''): static;

    public function apply(array|string $args = ''): static;

    public function checkoutIndex(array|string $args = ''): static;

    public function commitGraph(string $subCommand, array|string $args = ''): static;

    public function commitTree(array|string $args = ''): static;

    public function hashObject(array|string $args = ''): static;

    public function indexPack(array|string $args = ''): static;

    public function mergeFile(array|string $args = ''): static;

    public function mergeIndex(array|string $args = ''): static;

    public function mktag(array|string $args = ''): static;

    public function mktree(array|string $args = ''): static;

    public function multiPackIndex(array|string $args = ''): static;

    public function packObjects(array|string $args = ''): static;

    public function prunePacked(array|string $args = ''): static;

    public function readTree(array|string $args = ''): static;

    public function symbolicRef(array|string $args = ''): static;

    public function unpackObjects(array|string $args = ''): static;

    public function updateIndex(array|string $args = ''): static;

    public function updateRef(array|string $args = ''): static;

    public function writeTree(array|string $args = ''): static;

    public function catFile(array|string $args = ''): static;

    public function cherry(array|string $args = ''): static;

    public function diffFiles(array|string $args = ''): static;

    public function diffIndex(array|string $args = ''): static;

    public function diffTree(array|string $args = ''): static;

    public function forEachRef(array|string $args = ''): static;

    public function forEachRepo(array|string $args = ''): static;

    public function getTarCommitId(array|string $args = ''): static;

    public function lsFiles(array|string $args = ''): static;

    public function lsRemote(array|string $args = ''): static;

    public function lsTree(array|string $args = ''): static;

    public function mergeBase(array|string $args = ''): static;

    public function nameRev(array|string $args = ''): static;

    public function packRedundant(array|string $args = ''): static;

    public function revList(array|string $args = ''): static;

    public function revParse(array|string $args = ''): static;

    public function showIndex(array|string $args = ''): static;

    public function showRef(array|string $args = ''): static;

    public function unpackFile(array|string $args = ''): static;

    public function var(array|string $args = ''): static;

    public function verifyPack(array|string $args = ''): static;

    public function fetchPack(array|string $args = ''): static;

    public function sendPack(array|string $args = ''): static;

    public function httpFetch(array|string $args = ''): static;

    public function httpPush(array|string $args = ''): static;

    public function receivePack(array|string $args = ''): static;

    public function uploadArchive(array|string $args = ''): static;

    public function checkAttr(array|string $args = ''): static;

    public function checkIgnore(array|string $args = ''): static;

    public function checkMailmap(array|string $args = ''): static;

    public function checkRefFormat(array|string $args = ''): static;

    public function column(array|string $args = ''): static;

    public function credential(string $subCommand, array|string $args = ''): static;

    public function fmtMergeMsg(array|string $args = ''): static;

    public function hook(string $subCommand, array|string $args = ''): static;

    public function interpretTrailers(array|string $args = ''): static;

    public function maininfo(array|string $args = ''): static;

    public function mailsplit(array|string $args = ''): static;

    public function mergeOneFile(array|string $args = ''): static;

    public function patchId(array|string $args = ''): static;

    public function stripspace(array|string $args = ''): static;
}
