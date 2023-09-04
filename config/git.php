<?php

return [
    'providers' => [
        'git' => \Aybarsm\Laravel\Git\Git::class,
        'gitRepo' => \Aybarsm\Laravel\Git\GitRepo::class,
    ],
    'repos' => [
        'default' => base_path(),
    ],
    'commands' => [
        'bisect' => [
            'subcommands' => ['start', 'bad', 'new', 'good', 'old', 'terms', 'skip', 'reset', 'visualize', 'view', 'replay', 'log', 'run'],
        ],
        'bundle' => [
            'subcommands' => ['create', 'verify', 'list-heads', 'unbundle'],
        ],
        'maintenance' => [
            'subcommands' => ['run', 'start', 'stop', 'register', 'unregister'],
        ],
        'notes' => [
            'subcommands' => ['list', 'add', 'copy', 'append', 'edit', 'show', 'merge', 'remove', 'prune', 'get-ref'],
        ],
        'sparse-checkout' => [
            'subcommands' => ['init', 'list', 'set', 'add', 'reapply', 'disable', 'check-rules'],
        ],
        'stash' => [
            'subcommands' => ['list', 'show', 'drop', 'pop', 'apply', 'branch', 'push', 'save', 'clear', 'create', 'store'],
        ],
        'submodule' => [
            'subcommand_prefixes' => ['--quiet'],
            'subcommands' => ['add', 'status', 'init', 'deinit', 'update', 'set-branch', 'set-url', 'summary', 'foreach', 'sync', 'absorbgitdirs'],
        ],
        'worktree' => [
            'subcommands' => ['add', 'list', 'lock', 'move', 'prune', 'remove', 'repair', 'unlock'],
        ],
        'reflog' => [
            'subcommands' => ['show', 'expire', 'delete', 'exists'],
        ],
        'remote' => [
            'subcommands' => ['add', 'rename', 'remove', 'set-head', 'set-branches', 'get-url', 'set-url', 'show', 'prune', 'update'],
        ],
        'p4' => [
            'subcommands' => ['clone', 'sync', 'rebase', 'submit'],
        ],
        'commit-graph' => [
            'subcommands' => ['verify', 'write'],
        ],
        'credential' => [
            'subcommands' => ['fill', 'approve', 'reject'],
        ],
        'hook' => [
            'subcommands' => ['run'],
        ],
    ],
    'submodule' => [
        'scan' => [
            'name' => '$name',
            'path' => '$toplevel/$displaypath',
            'toplevel' => '$toplevel',
            'displayPath' => '$displaypath',
            'smPath' => '$sm_path',
            'smPathFull' => '$toplevel/.git/modules/$sm_path',
            'sha1' => '$sha1',
            'branch' => '$(git symbolic-ref --short HEAD)',
            'tag' => '$(git describe --tags 2>/dev/null)',
            'dirty' => '$(git diff --quiet || echo "1")',
        ],
    ],
];
