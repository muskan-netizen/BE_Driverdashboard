<?php 
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeTrait extends Command
{
    protected $signature = 'make:trait {name}';

    protected $description = 'Create a new trait';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $name = $this->argument('name'); 
        $traitName = Str::studly($name); //PascalCase or camel case function it will convert test_trait as TestTrait

        $traitPath = app_path("Traits/{$traitName}.php");

        if (file_exists($traitPath)) {
            $this->error("The trait {$traitName} already exists!");
            return;
        }

        $stub = file_get_contents(base_path('stubs/trait.stub'));
        $stub = str_replace('{{ TraitName }}', $traitName, $stub);

        file_put_contents($traitPath, $stub);

        $this->info("Trait {$traitName} created successfully!");
    }
}
