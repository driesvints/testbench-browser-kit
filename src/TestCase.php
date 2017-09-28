<?php

namespace Orchestra\Testbench\BrowserKit;

use Mockery;
use Illuminate\Support\Facades\Facade;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\Traits\WithFactories;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithoutEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Traits\CreatesApplication;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Orchestra\Testbench\Traits\WithLaravelMigrations;
use Orchestra\Testbench\Traits\WithLoadMigrationsFrom;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\BrowserKitTesting\Concerns\ImpersonatesUsers;
use Laravel\BrowserKitTesting\Concerns\MakesHttpRequests;
use Laravel\BrowserKitTesting\Concerns\InteractsWithConsole;
use Laravel\BrowserKitTesting\Concerns\InteractsWithSession;
use Laravel\BrowserKitTesting\Concerns\InteractsWithDatabase;
use Laravel\BrowserKitTesting\Concerns\InteractsWithContainer;
use Laravel\BrowserKitTesting\Concerns\MocksApplicationServices;
use Laravel\BrowserKitTesting\Concerns\InteractsWithAuthentication;
use Orchestra\Testbench\BrowserKit\Contracts\TestCase as TestCaseContract;

abstract class TestCase extends BaseTestCase implements TestCaseContract
{
    use CreatesApplication,
        InteractsWithContainer,
        MakesHttpRequests,
        ImpersonatesUsers,
        InteractsWithAuthentication,
        InteractsWithConsole,
        InteractsWithDatabase,
        InteractsWithSession,
        MocksApplicationServices,
        WithFactories,
        WithLaravelMigrations,
        WithLoadMigrationsFrom;

    /**
     * The Illuminate application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * The callbacks that should be run after the application is created.
     *
     * @var array
     */
    protected $afterApplicationCreatedCallbacks = [];

    /**
     * The callbacks that should be run before the application is destroyed.
     *
     * @var array
     */
    protected $beforeApplicationDestroyedCallbacks = [];

    /**
     * Indicates if we have made it through the base setUp function.
     *
     * @var bool
     */
    protected $setUpHasRun = false;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp()
    {
        if (! $this->app) {
            $this->refreshApplication();
        }

        $this->setUpTraits();

        foreach ($this->afterApplicationCreatedCallbacks as $callback) {
            call_user_func($callback);
        }

        Facade::clearResolvedInstances();

        Model::setEventDispatcher($this->app['events']);

        $this->setUpHasRun = true;
    }

    /**
     * Refresh the application instance.
     *
     * @return void
     */
    protected function refreshApplication()
    {
        putenv('APP_ENV=testing');

        $this->app = $this->createApplication();
    }

    /**
     * Boot the testing helper traits.
     *
     * @return array
     */
    protected function setUpTraits()
    {
        $uses = array_flip(class_uses_recursive(static::class));

        if (isset($uses[RefreshDatabase::class])) {
            $this->refreshDatabase();
        }

        if (isset($uses[DatabaseMigrations::class])) {
            $this->runDatabaseMigrations();
        }

        if (isset($uses[DatabaseTransactions::class])) {
            $this->beginDatabaseTransaction();
        }

        if (isset($uses[WithoutMiddleware::class])) {
            $this->disableMiddlewareForAllTests();
        }

        if (isset($uses[WithoutEvents::class])) {
            $this->disableEventsForAllTests();
        }

        return $uses;
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    protected function tearDown()
    {
        if ($this->app) {
            foreach ($this->beforeApplicationDestroyedCallbacks as $callback) {
                call_user_func($callback);
            }

            $this->app->flush();

            $this->app = null;
        }

        $this->setUpHasRun = false;

        if (property_exists($this, 'serverVariables')) {
            $this->serverVariables = [];
        }

        if (class_exists('Mockery')) {
            Mockery::close();
        }

        $this->afterApplicationCreatedCallbacks = [];
        $this->beforeApplicationDestroyedCallbacks = [];
    }

    /**
     * Register a callback to be run after the application is created.
     *
     * @param  callable  $callback
     *
     * @return void
     */
    protected function afterApplicationCreated(callable $callback)
    {
        $this->afterApplicationCreatedCallbacks[] = $callback;

        if ($this->setUpHasRun) {
            call_user_func($callback);
        }
    }

    /**
     * Register a callback to be run before the application is destroyed.
     *
     * @param  callable  $callback
     *
     * @return void
     */
    protected function beforeApplicationDestroyed(callable $callback)
    {
        $this->beforeApplicationDestroyedCallbacks[] = $callback;
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application   $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Define your environment setup.
    }
}
