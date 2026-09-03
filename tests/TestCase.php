<?php

namespace Wsmallnews\FilamentNestedset\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Kalnoy\Nestedset\NestedSetServiceProvider;
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
            FilamentNestedsetServiceProvider::class,
            NestedSetServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Package default config
        config()->set('sn-filament-nestedset.allow_delete_parent', false);
        config()->set('sn-filament-nestedset.allow_delete_root', false);
        config()->set('sn-filament-nestedset.create_action_modal_show_parent_select', true);
        config()->set('sn-filament-nestedset.show_create_child_node_action_in_row', true);
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
