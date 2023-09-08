## What It Does
Laravel service provider package to manage git repos within or outside the Laravel application

## Installation

You can install the package via composer:

```bash
composer require aybarsm/laravel-git
```

You can publish the config file by:

```bash
php artisan vendor:publish --provider="Aybarsm\Laravel\Git\GitServiceProvider" --tag=config
```

## Configure Git Provider

You can change the concretes by extending the classes, remove or add new repos and modify commands with subcommands.

##### Note: The list of the commands only covers the commands with subcommands. Almost all git command has been implemented to the concrete and interface in the package.  

```php
return [
    'repos' => [
        'default' => base_path(),
    ],
    'concretes' => [
        'Git' => \Aybarsm\Laravel\Git\Git::class,
        'GitRepo' => \Aybarsm\Laravel\Git\GitRepo::class,
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
];
```

## Usage

You can call the concrete Git by either Git::class Facade or with helper function git(). Another helper function of gitRepo($repoName) has also implemented to directly call pre-defined Git Repos.

### Example
```php
use Aybarsm\Laravel\Support\Enums\ProcessReturnType;

$git = git();
$repo = $git->repo(); // Returns the default pre-defined repo
// or you can directly reach the repo
// $repo = gitRepo();

if ($repo->isReady() && $repo->isDirty()){
    // arguments accepts strings or cli type arrays like arg, --arg=value, -arg value or -arg
    $repo->commit(
    args: [
        '-a',
        '-m' => '"v1.0.0"' 
        ]
    )
    // Git and GitRepo concretes are already uses Laravel's Conditionable trait however chaining made easier with pre-defined whenSuccessful and whenFailed methods.
    ->whenSuccessful(
        callback: fn ($repoInstance) => $repoInstance->tag('v1.0.0'),
        default: function ($repoInstance) {
                Log::info('Git Repo Command Error', (array)$repoInstance->result(ProcessReturnType::ALL_OUTPUT));
                return $repoInstance;
            }
    )
    ->whenSuccessful(
        callback: fn ($repoInstance) => $repoInstance->push('origin v1.0.0'),
        default: function ($repoInstance) {
                Log::info('Git Repo Command Error', (array)$repoInstance->result(ProcessReturnType::ALL_OUTPUT));
                return $repoInstance;
            }
    );
}
```
