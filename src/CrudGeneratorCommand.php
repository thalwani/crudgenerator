<?php

namespace Thalwani\CrudGenerator;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use DB;

class CrudGeneratorCommand extends Command
{
    protected $signature = 'crud:generator
        {name : Class (singular) for example User}';

    protected $description = 'Create CRUD operations';

    /**
     * The views that need to be exported.
     *
     * @var array
     */
    protected $views = [
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $name = $this->argument('name');

        $this->controller($name);
        $this->model($name);
        $this->request($name);
        $this->list($name);
        $this->create($name);
        $this->edit($name);

        File::append(base_path('routes/web.php'), 'Route::resource(\'' . str_plural(strtolower($name)) . "', '{$name}Controller');");
    }

    protected function controller($name)
    {
        $controllerTemplate = str_replace(
            [
                '{{modelName}}',
                '{{modelNamePluralLowerCase}}',
                '{{modelNameSingularLowerCase}}'
            ],
            [
                $name,
                strtolower(str_plural($name)),
                strtolower($name)
            ],
            $this->getStub('Controller')
        );

        file_put_contents(app_path("/Http/Controllers/{$name}Controller.php"), $controllerTemplate);
    }

    protected function model($name)
    {
        $modelTemplate = str_replace(
            ['{{modelName}}'],
            [$name],
            $this->getStub('Model')
        );

        file_put_contents(app_path("/{$name}.php"), $modelTemplate);
    }

    protected function request($name)
    {
        $requestTemplate = str_replace(
            ['{{modelName}}'],
            [$name],
            $this->getStub('Request')
        );

        if(!file_exists($path = app_path('/Http/Requests')))
            mkdir($path, 0777, true);

        file_put_contents(app_path("/Http/Requests/{$name}Request.php"), $requestTemplate);
    }

    protected function list($name)
    {
        $columns = $this->getColumns(str_plural($name));

        $i = 0;
        $head = '';
        $body = '';
        foreach ($columns as $key => $value) {
            $eol = ($i == count($columns) - 1) ? '' : PHP_EOL;
            $head .= '             <th>' . $key . '</th>' . $eol;
            $body .= '                <td>{{ $' . strtolower($name) .'->' . $key . '}}</td>' . $eol;
            $i ++;
        }

        $listTemplate = str_replace(
            [
                '{{modelName}}',
                '{{modelNamePlural}}',
                '{{modelNamePluralLowerCase}}',
                '{{modelNameSingularLowerCase}}',
                '{{head}}',
                '{{body}}'
            ],
            [
                $name,
                str_plural($name),
                strtolower(str_plural($name)),
                strtolower($name),
                $head,
                $body
            ],
            $this->getStub('views/list')
        );

        if(!file_exists($path = resource_path('views/templates/' . str_plural(strtolower($name)))))
            mkdir($path, 0777, true);

        file_put_contents(resource_path('views/templates/' . str_plural(strtolower($name))) . '/list.blade.php', $listTemplate);
    }

    protected function create($name)
    {
        $columns = $this->getColumns(str_plural($name));

        $i = 0;
        $fields = '';
        foreach ($columns as $key => $type) {
            $eol = ($i == count($columns) - 1) ? '' : PHP_EOL;
            $fields .= '                <div class="form-group">
                  <label for="name">' . $key . '</label>
                  ' . $this->getInput($key, $type) . ' 
                </div>' . $eol;
            $i ++;
        }

        $listTemplate = str_replace(
            [
                '{{modelName}}',
                '{{modelNamePlural}}',
                '{{modelNamePluralLowerCase}}',
                '{{modelNameSingularLowerCase}}',
                '{{fields}}'
            ],
            [
                $name,
                str_plural($name),
                strtolower(str_plural($name)),
                strtolower($name),
                $fields
            ],
            $this->getStub('views/create')
        );

        if(!file_exists($path = resource_path('views/templates/' . str_plural(strtolower($name)))))
            mkdir($path, 0777, true);

        file_put_contents(resource_path('views/templates/' . str_plural(strtolower($name))) . '/create.blade.php', $listTemplate);
    }

    protected function edit($name)
    {
        $columns = $this->getColumns(str_plural($name));

        $i = 0;
        $fields = '';
        foreach ($columns as $key => $type) {
            $eol = ($i == count($columns) - 1) ? '' : PHP_EOL;
            $fields .= '                <div class="form-group">
                  <label for="name">' . $key . '</label>
                  ' . $this->getInput($key, $type, '{{ $' . strtolower($name) . '->' . $key . ' }}') . '
                </div>' . $eol;
            $i ++;
        }

        $listTemplate = str_replace(
            [
                '{{modelName}}',
                '{{modelNamePlural}}',
                '{{modelNamePluralLowerCase}}',
                '{{modelNameSingularLowerCase}}',
                '{{fields}}'
            ],
            [
                $name,
                str_plural($name),
                strtolower(str_plural($name)),
                strtolower($name),
                $fields
            ],
            $this->getStub('views/edit')
        );

        if(!file_exists($path = resource_path('views/templates/' . str_plural(strtolower($name)))))
            mkdir($path, 0777, true);

        file_put_contents(resource_path('views/templates/' . str_plural(strtolower($name))) . '/edit.blade.php', $listTemplate);
    }

    /**
     * Export the views.
     *
     * @return void
     */
    protected function exportViews($name)
    {
        $directory = resource_path('views/templates/' . str_plural(strtolower($name)));

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        foreach ($this->views as $key => $value) {
            $stub = resource_path('/stubs/views/'.$key);
            $stubContent = file_get_contents($stub);

            if (file_exists($view = $directory . '/' . $value)) {
                if (! $this->confirm("The [{$value}] view already exists. Do you want to replace it?")) {
                    continue;
                }
            }

            $viewTemplate = str_replace(
                [
                    '{{modelName}}',
                    '{{modelNamePlural}}',
                    '{{modelNamePluralLowerCase}}',
                    '{{modelNameSingularLowerCase}}'
                ],
                [
                    $name,
                    str_plural($name),
                    strtolower(str_plural($name)),
                    strtolower($name)
                ],
                $stubContent
            );

            file_put_contents($view, $viewTemplate);
        }
    }

    protected function getStub($type)
    {
        return file_get_contents(__DIR__ . "/stubs/$type.stub");
    }

    protected function getColumns($table) {
        $columns = [];
        $schema = DB::getDoctrineSchemaManager();
        $dbColumns = $schema->listTableColumns($table); 
        foreach ($dbColumns as $column) { 
            $columns[$column->getName()] = $column->getType()->getName();
        }

        return $columns;
    }

    protected function getInput($key, $type, $value = '') {
        switch ($type) {
            case 'datetime':
                return '<div class="input-group date">
                            <div class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </div>
                            <input type="text" class="form-control datepicker" name="' . $key . '" value="' . $value . '"/>
                        </div>';
            break;

            case 'text':
                return '<textarea id="ckeditor" placeholder="Message" class="form-control" rows="15" name="' . $key . '">' . $value . '</textarea>';
            break;

            default:
                return '<input type="text" class="form-control" name="' . $key . '" value="' . $value . '"/>';
            break;
        }
    }
}