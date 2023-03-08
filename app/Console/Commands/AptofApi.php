<?php

namespace App\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Pluralizer;
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
    private Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

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
    }

    private function setup()
    {
        if (!$this->files->isDirectory($this->apiFolder)) {
            $this->files->makeDirectory($this->apiFolder, 0777, true, true);
        }

        if (!$this->files->exists($this->baseControllerPath)) {
            $this->createBaseController();
        }
    }

    private function ensureErrorFree(string $model, string $controller)
    {
        if ($this->files->exists($this->getModelPath($model))) {
            $this->error("Model $model already exists.");
            throw new Exception("Model $model already exists.");
        }

        if ($this->files->exists($this->getApiControllerPath($controller))) {
            $this->error("Controller $controller already exists.");
            throw new Exception("Controller $controller already exists.");
        }

        if ($this->files->exists($this->getFactoryPath($model))) {
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

    private function createBaseController(): void
    {
        $content = file_get_contents($this->baseControllerStub);
        $path = $this->baseControllerPath;
        $this->files->put($path, $content);
        $this->info("File: {$path} is created.");
    }

    private function createModel(string $model)
    {
        $content = file_get_contents($this->modelStub);
        $content = str_replace('ModelStub', $model, $content);
        $path = $this->getModelPath($model);
        $this->files->put($path, $content);
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
        $content = file_get_contents($this->factoryStub);
        $content = str_replace('ModelStub', $model, $content);
        $path = $this->getFactoryPath($model);
        $this->files->put($path, $content);
        $this->info("Factory {$model}Factory is created");
    }


    public function createController(string $model, string $controller)
    {
        $content = file_get_contents($this->apiControllerStub);
        $path = $this->getApiControllerPath($controller);
        // ControllerStub, ModelStub modelStub
        $this->files->put($path, $content);
        $this->info("Controller {$controller} is created");
    }
}
