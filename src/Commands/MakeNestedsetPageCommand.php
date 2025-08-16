<?php

namespace Wsmallnews\FilamentNestedset\Commands;

use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[AsCommand(name: 'make:filament-nestedset-page', description: 'Create a new Filament Nestedset Page')]
class MakeNestedsetPageCommand extends Command
{
    public $signature = 'make:filament-nestedset-page {name?} {model?} {--panel=} {--F|force}';

    public $description = 'Create a new Filament Nestedset Page';


    public function handle(Filesystem $filesystem): int
    {
        $page = $this->parsePage();

        $pageClass = (string) str($page)->afterLast('\\');
        $pageNamespace = str($page)->contains('\\') ? (string) str($page)->beforeLast('\\') : '';

        $panel = $this->getPanel();

        $pageDirectories = $panel->getPageDirectories();
        $pageNamespaces = $panel->getPageNamespaces();

        foreach ($pageDirectories as $pageIndex => $pageDirectory) {
            if (str($pageDirectory)->startsWith(base_path('vendor'))) {
                unset($pageDirectories[$pageIndex], $pageNamespaces[$pageIndex]);
            }
        }

        $namespace = (\count($pageNamespaces) > 1)
            ? select(
                label: 'Which namespace would you like to create this in?',
                options: $pageNamespaces,
            )
            : (Arr::first($pageNamespaces) ?? 'App\\Admin\\Pages');

        $path = (\count($pageDirectories) > 1)
            ? $pageDirectories[array_search($namespace, $pageNamespaces, true)]
            : (Arr::first($pageDirectories) ?? app_path('Admin/Pages/'));

        $path = (string) str($page)
            ->prepend('/')
            ->prepend($path)
            ->replace('\\', '/')
            ->replace('//', '/')
            ->append('.php');

        if (!$this->option('force') && $this->checkForCollision([$path])) {
            return self::INVALID;
        }

        $potentialCluster = (string) str($namespace)->beforeLast('\Pages');
        $clusterAssignment = null;
        $clusterImport = null;

        if (
            class_exists($potentialCluster)
            && is_subclass_of($potentialCluster, Cluster::class)
            && filled($potentialCluster)
        ) {
            $clusterAssignment = $this->indentString(
                PHP_EOL . PHP_EOL . 'protected static ?string $cluster = ' . class_basename(
                    $potentialCluster,
                ) . '::class;',
            );

            $clusterImport = "use {$potentialCluster};" . PHP_EOL;
        }

        $model = $this->getModelStmt();

        $this->copyStub(
            filesystem: $filesystem,
            targetPath: $path,
            replacements: [
                'class' => $pageClass,
                'clusterAssignment' => $clusterAssignment,
                'clusterImport' => $clusterImport,
                'namespace' => $namespace . ($pageNamespace !== '' ? "\\{$pageNamespace}" : ''),
                'model' => $model,
            ],
        );

        $this->components->info("Admin tree page [{$path}] created successfully.");

        return self::SUCCESS;
    }

    public function parsePage(): string
    {
        $pageName = $this->argument('name') ?? text(
            label: 'What is the nestedset page name?',
            placeholder: 'NestedsetPage',
            required: true,
        );

        if (preg_match('([^A-Za-z0-9_/\\\\])', $pageName)) {
            throw new InvalidArgumentException('Page name contains invalid characters.');
        }

        return (string) str($pageName)
            ->trim('\\/')
            ->replace('/', '\\');
    }

    private function copyStub(
        Filesystem $filesystem,
        string $targetPath,
        array $replacements = [],
    ): void {
        $stubPath = base_path('stubs/NestedsetPage.stub');

        if (!$this->fileExists($stubPath)) {
            $stubPath = $this->getDefaultStubPath() . '/NestedsetPage.stub';
        }

        $stub = str($filesystem->get($stubPath));

        foreach ($replacements as $key => $replacement) {
            $stub = $stub->replace("{{ {$key} }}", $replacement);
        }

        $this->writeFile($targetPath, (string) $stub);
    }

    private function parseModel(string $model): string
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        $model = (string) str($model)->ltrim('\\/')->replace('/', '\\');

        $rootNamespace = $this->laravel->getNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        return '\\' . $rootNamespace . 'Models\\' . $model;
    }

    private function getPanel(): Panel
    {
        $panel = $this->option('panel');

        if ($panel) {
            return Filament::getPanel($panel, isStrict: false);
        }

        $panels = Filament::getPanels();

        if (\count($panels) === 1) {
            return Arr::first($panels);
        }

        $panelIndex = select(
            label: 'Which panel would you like to create this in?',
            options: array_map(
                static fn(Panel $panel): string => $panel->getId(),
                $panels,
            ),
            default: Filament::getDefaultPanel()->getId(),
        );

        return $panels[$panelIndex];
    }

    private function getModelStmt(): string
    {
        $placeholder = 'null;       // TODO: Set nestedset model';

        $modelName = $this->argument('model') ?? text(
            label: 'What is the model class?',
            placeholder: 'NestedsetModel',
        );

        if (blank($modelName)) {
            return $placeholder;
        }

        $modelClass = $this->parseModel($modelName);

        if (!class_exists($modelClass)) {
            $this->warn("Model '{$modelClass}' not found");

            return $placeholder;
        }

        return "{$modelClass}::class;";
    }
}
