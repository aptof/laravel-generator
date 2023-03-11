<?php

namespace App\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Console\Command;


class AptofApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aptof:api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates API Controller';

    private $factoryFolder = './database/factories/';
    private $modelFolder = './app/Models/';
    private $controllerPath = './app/Http/Controllers/Api/';
    private $baseControllerPath = './app/Http/Controllers/BaseController.php';
    private $apiFolder = './app/Http/Controllers/Api';
    private $baseControllerStub = './stubs/BaseController.stub';
    private $apiControllerStub = './stubs/ApiController.stub';
    private $factoryStub = './stubs/Factory.stub';
    private $modelStub = './stubs/Model.stub';

    private $routeStub = './stubs/Route.stub';
    private $api = './routes/api.php';
    private $apiStub = './stubs/Api.stub';

    private $testFolder = './tests/Apis';
    private $testTraitStub = './stubs/TestTrait.stub';
    private $testTraitPath = './tests/ApiTestTrait.php';
    private $testStub = './stubs/Test.stub';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->setup();
        $model = ucfirst($this->ask('Name of the Model:'));
        $controller = $model . 'ApiController';

        $this->ensureErrorFree($model, $controller);
        $this->createModel($model);
        $this->createMigration($model);
        $this->createFactory($model);
        $this->createController($model, $controller);
        $this->createTest($model, $controller);
        $this->addRoute($model, $controller);
    }

    private function setup()
    {
        if (!File::isDirectory($this->apiFolder)) {
            File::makeDirectory($this->apiFolder, 0777, true, true);

            // Update route file to insert //CURSOR
            $this->updateRouteFile();
        }

        if (!File::exists($this->baseControllerPath)) {
            $this->createBaseController();
        }

        if (!File::isDirectory($this->testFolder)) {
            File::makeDirectory($this->testFolder);

            //CreateTestTrait
            $content = File::get($this->testTraitStub);
            File::put($this->testTraitPath, $content);
        }
    }

    private function updateRouteFile()
    {
        $content = File::get($this->apiStub);
        File::put($this->api, $content);
    }

    private function ensureErrorFree(string $model, string $controller)
    {
        if (File::exists($this->getModelPath($model))) {
            $this->error("Model $model already exists.");
            throw new Exception("Model $model already exists.");
        }

        if (File::exists($this->getApiControllerPath($controller))) {
            $this->error("Controller $controller already exists.");
            throw new Exception("Controller $controller already exists.");
        }

        if (File::exists($this->getFactoryPath($model))) {
            $this->error("Factory {$model}Factory already exists");
            throw new Exception("Factory {$model}Factory already exists");
        }
    }

    /**
     * Returns the model full path;
     */
    private function getModelPath(string $model): string
    {
        return $this->modelFolder . $model . '.php';
    }

    private function getFactoryPath(string $model): string
    {
        return $this->factoryFolder . $model . 'Factory.php';
    }

    private function getApiControllerPath(string $controller)
    {
        return $this->controllerPath . $controller . '.php';
    }

    private function getApiTestPath(string $controller)
    {
        return $this->testFolder . '/' . $controller . 'Test.php';
    }

    private function createBaseController(): void
    {
        $content = File::get($this->baseControllerStub);
        $path = $this->baseControllerPath;
        File::put($path, $content);
        $this->info("File: {$path} is created.");
    }

    private function createModel(string $model)
    {
        $content = File::get($this->modelStub);
        $content = Str::replace('ModelStub', $model, $content);
        $path = $this->getModelPath($model);
        File::put($path, $content);
        $this->info("Model: {$model} is created.");
    }

    public function createMigration(string $model)
    {
        $model = strtolower(Pluralizer::plural($model));
        $model = "create_{$model}_table";
        $this->call('make:migration', ['name' => $model]);
    }

    public function createFactory(string $model)
    {
        $content = File::get($this->factoryStub);
        $content = Str::replace('ModelStub', $model, $content);
        $path = $this->getFactoryPath($model);
        File::put($path, $content);
        $this->info("Factory {$model}Factory is created");
    }


    public function createController(string $model, string $controller)
    {
        $content = File::get($this->apiControllerStub);
        $content = Str::replace('ModelStub', $model, $content);
        $content = Str::replace('modelStub', Str::camel($model), $content);
        $content = Str::replace('ControllerStub', $controller, $content);

        $path = $this->getApiControllerPath($controller);
        File::put($path, $content);
        $this->info("Controller {$controller} is created");
    }

    public function addRoute(string $model, string $controller)
    {
        $stubContent = File::get($this->routeStub);
        $stubContent = Str::replace('modelsStub', Pluralizer::plural(Str::lower($model)), $stubContent);
        $stubContent = Str::replace('ControllerStub', $controller, $stubContent);

        $content = File::get($this->api);
        $content = Str::replace('//CURSOR', $stubContent, $content);
        File::put($this->api, $content);
        $this->info("Routes are updated");
    }

    public function createTest(string $model, string $controller)
    {
        $content = File::get($this->testStub);
        $content = Str::replace('ModelStub', $model, $content);
        $content = Str::replace('modelStub', Str::lower($model), $content);
        $content = Str::replace('modelsStub', Pluralizer::plural(Str::lower($model)), $content);
        $content = Str::replace('ControllerStub', $controller, $content);

        File::put($this->getApiTestPath($controller), $content);
        $this->info("{$controller}Test is created");
    }
}
