<?php

namespace Wsmallnews\FilamentNestedset\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use CodeWithDennis\FilamentSelectTree\FilamentSelectTreeServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Kalnoy\Nestedset\NestedSetServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Wsmallnews\FilamentNestedset\FilamentNestedsetServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Wsmallnews\\FilamentNestedset\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            FilamentSelectTreeServiceProvider::class,
            SupportServiceProvider::class,
            SchemasServiceProvider::class,
            FormsServiceProvider::class,
            ActionsServiceProvider::class,
            NotificationsServiceProvider::class,
            FilamentServiceProvider::class,
            LivewireServiceProvider::class,
            FilamentNestedsetServiceProvider::class,
            NestedSetServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        // Filament Action modal 渲染需要加密器
        config()->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        config()->set('database.default', 'testing');

        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('sn-filament-nestedset.autoload_assets', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('test_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('scope_type')->nullable();
            $table->unsignedBigInteger('scope_id')->default(0);
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('status')->default('normal');
            $table->json('options')->nullable();
            $table->nestedSet();
            $table->timestamps();
        });

        Schema::create('plain_models', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }
}
