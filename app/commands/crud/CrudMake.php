<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CrudMake extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name     = 'crud:make';
    protected $schema   = "";
    protected $variable = "";
    protected $viewFolderName = "";
    protected $modelName = "";
    protected $validation = true;
    protected $controllerName = "";
    protected $caption = "";
    protected $allMocks = array();
    protected $fieldTypes = array("text","hidden","digit","textarea","password","file","email");
    protected $createForm = array(
        "text"      => "{{ Form::text('{{name}}', Input::old('{{name}}'), array('class' => 'form-control')) }}",
        "hidden"    => "{{ Form::hidden('{{name}}', Input::old('{{name}}') ) }}",
        "textarea"  => "{{ Form::textarea('{{name}}', Input::old('{{name}}'), array('class' => 'form-control')) }}",
        "password"  => "{{ Form::password('{{name}}', array('class' => 'form-control')) }}",
        "digit"     => "{{ Form::text('{{name}}', Input::old('{{name}}'), array('class' => 'form-control')) }}" ,
        "file"      => "{{ Form::file('{{name}}', Input::old('{{name}}'), array('class' => 'form-control')) }}" ,
        "email"     => "{{ Form::email('{{name}}', Input::old('{{name}}'), array('class' => 'form-control')) }}" ,
        "title"     => "{{ Form::label('{{id}}', '{{title}}') }}",
        "div"       => '<div class="form-group">',
        "/div"      =>"</div>"
    );
    protected $updateForm = array(
        "text"      => "{{ Form::text('{{name}}', {{value}} , array('class' => 'form-control')) }}",
        "hidden"    => "{{ Form::hidden('{{name}}',{{value}} ) }}",
        "textarea"  => "{{ Form::textarea('{{name}}', {{value}}, array('class' => 'form-control')) }}",
        "password"  => "{{ Form::password('{{name}}', array('class' => 'form-control')) }}",
        "digit"     => "{{ Form::text('{{name}}', {{value}}, array('class' => 'form-control')) }}" ,
        "file"      => "{{ Form::file('{{name}}') }}" ,
        "email"     => "{{ Form::email('{{name}}', {{value}} , array('class' => 'form-control')) }}" ,
        "title"     => "{{ Form::label('{{id}}', '{{title}}') }}",
        "div"       => '<div class="form-group">',
        "/div"      =>"</div>"
    );
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create View , Controller [Resource] for CRUD';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$fileAddress = File::files($this->getPath("mocks/"));
        foreach($fileAddress as $file){
            $className = basename($file, '.php');
            include_once($file);
            $this->allMocks[] = $className;
        }
        foreach($this->allMocks as $mock){
            $this->initMock($mock);
        }
        $this->response(count($this->allMocks));
	}
    protected function response($mocks){
        if ($mocks > 1)
            $this->line("CRUDS where created");
        else
            $this->line("CRUD was created");
    }
    protected function initMock($mock){
        $this->viewFolderName = $mock::$viewFolderName;
        $this->schema         = $mock::$schema;
        $this->modelName      = $mock::$modelName;
        $this->controllerName = $mock::$resourceName;
        $this->validation     = $mock::$validation;
        $this->variable       = "$".strtolower($mock::$modelName);
        $this->caption        = strtolower($mock::$modelName);
        $this->createViewFolder();
        $this->createViewFiles();
        $this->createResource();
    }
	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
		);
	}
	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
		);
	}

    protected function getPath($path){
        return app_path($path);
    }

    protected function createFields($type,$message){
        $result = "";
        foreach($this->schema as $field=>$value){
            $field = strtolower($field);
            $searchArray = explode("|",$value);
            if(in_array($type,$searchArray) and !in_array("deny",$searchArray)){
                if(in_array("hash",$searchArray)){
                    $result.=$this->tab(2)
                        .$this->variable
                        .'->'
                        .$field
                        ." = "
                        .'Hash::make(Input::get("'.$field.'"));'
                        .$this->newLine();
                }else{
                    $result.=$this->tab(2).
                        $this->variable
                        .'->'
                        .$field.
                        " = "
                        .'Input::get("'.$field.'");'
                        .$this->newLine();
                }
            }
        }
        $result.=$this->tab(2)
            .$this->variable.
            '->save();'
            .$this->newLine()
            .$this->tab(2)
            ."Session::flash('message', '{$message} {$this->caption}');
			return Redirect::to('$this->caption');"
            .$this->newLine();
        return $result;
    }

    protected function getValidation(){
        return '$validator = Validator::make(Input::all(), '.$this->modelName.'::$rules);
        if ($validator->fails()) {
			return Redirect::back()
				->withErrors($validator)
				->withInput(Input::except("password"));
		}';
    }

    protected function createResource(){
        $template = File::get($this->getPath("commands/crud/template/resource.crud"));
        $create = $this->variable.' = new '.$this->modelName .";". $this->newline();
        $update = $this->variable.' = '.$this->modelName.'::find($id);'. $this->newline();
        $validation="";
        if($this->validation){
            $validation = $this->getValidation();
        }
        $create.=$this->createFields("create","Successfully Created the ");
        $update.=$this->createFields("edit","Successfully Updated the ");
        $search = array(
            "ControllerName"  ,
            '{{all}}'        ,
            '{{model}}'  ,
            '{{viewFolderName}}' ,
            '{{variable}}' ,
            '{{store}}' ,
            '{{update}}',
            '{{validation}}',
            '{{caption}}',
        );

        $replace = array(
            $this->controllerName  ,
            $this->variable ,
            $this->modelName ,
            $this->viewFolderName ,
            $this->variable,
            $create,
            $update,
            $validation,
            $this->caption
        );
        $content = str_replace($search,$replace,$template);
        $this->checkControllerExit($content);
    }

    protected function checkControllerExit($content){
        $controllerPath = $this->getPath("controllers/").ucwords($this->controllerName).".php";
        if (File::exists($controllerPath)){
            if ($this->confirm("Are you sure you want to override the $this->controllerName ? [yes|no]",false))
            {
                File::put($controllerPath,$content);
            }
            else {
                $this->error("operation is terminated");
                die();
            }
        }
        else {
            File::put($controllerPath,$content);
        }
    }

    protected function createViewFolder(){
        $viewFolderName = $this->viewFolderName;
        if (!File::isDirectory($this->getPath("views/".$viewFolderName))){
            File::makeDirectory($this->getPath("views/".$viewFolderName), $mode = 0777, true, true);
        }
    }

    protected function createViewFiles(){
       $viewFolderName = $this->viewFolderName ;
       $model = $this->modelName ;
       $this->viewShow($viewFolderName,$model);
       $this->viewIndex($viewFolderName,$model);
       $this->viewCreate($viewFolderName,$model);
       $this->viewEdit($viewFolderName,$model);
    }

    protected function makeList($variable){
        $list="";
        foreach($this->schema as $fieldName=>$value){
            $title = $this->replaceUnderScore(ucwords($fieldName));
            $arraySearch = explode("|",$value);
            if (in_array("show",$arraySearch)){
                $sample = "<strong> {$title} : </strong> {{ $variable->{$fieldName} }}<br> \n";
                $list.=$sample;
            }
        }
        return $list;
    }

    protected function viewShow(){
        $showFileContent = File::get($this->getPath("commands/crud/template/view/show.crud"));
        $list = $this->makeList($this->variable);
        $find = array(
            '{{caption}}', '{{showingList}}'
        );
        $replace = array(
            $this->caption, $list
        );
        $content = $this->replaceString($find,$replace,$showFileContent);
        File::put($this->getPath("views/{$this->viewFolderName}/")."show.blade".".php",$content);
    }

    protected function replaceString($find,$replace,$content){
        return str_replace($find,$replace,$content);
    }

    protected function buildHeaderTable(){
        $header = "";
        foreach($this->schema as $fieldName=>$value){
            $title = $this->replaceUnderScore(ucwords($fieldName));
            $arraySearch = explode("|",$value);
            if (in_array("index",$arraySearch)){
                $sampleHead = "<td> {$title} </td> \n";
                $header.=$sampleHead;
            }
        }
        return $header;
    }

    protected function buildBodyTable(){
        $body = "";
        foreach($this->schema as $fieldName=>$value){
            $type  = strtolower($fieldName);
            $arraySearch = explode("|",$value);
            if (in_array("index",$arraySearch)){
                $sampleBody = '<td> {{ $value'. "->$type }} </td> \n";
                $body.=$sampleBody;
            }
        }
        return $body;
    }

    protected function viewIndex($viewFolderName,$model){

        $show   = File::get($this->getPath("commands/crud/template/view/index.crud"));
        $head   = $this->buildHeaderTable();
        $body   = $this->buildBodyTable();
        $search = array('{{caption}}', '{{variable}}', '{{head}}', '{{body}}');
        $replace = array($this->caption, $this->caption, $head, $body);
        $content = str_replace($search,$replace,$show);
        File::put($this->getPath("views/{$viewFolderName}/")."index.blade".".php",$content);
    }

    protected function viewCreate($viewFolderName,$model){
        $create = File::get($this->getPath("commands/crud/template/view/create.crud"));
        $form = "";
        foreach($this->schema as $fieldName=>$value){
            $fieldName = strtolower($fieldName);
            $formTitle = $this->replaceUnderScore(ucwords($fieldName));
            $arraySearch = explode("|",$value);
            if (in_array("create",$arraySearch)){
                foreach($this->fieldTypes as $fieldType){
                    if(in_array($fieldType,$arraySearch)){
                        $title   = $this->replaceTitle($fieldName,$formTitle);
                        $element = $this->replaceField($fieldName,$fieldType);
                        $form.= $this->makeHtmlForm($title,$element,$this->createForm,$arraySearch);
                    }
                }
            }
        }
        $search = array( '{{caption}}' ,'{{forms}}');
        $replace = array($this->caption ,$form);
        $content = str_replace($search,$replace,$create);
        File::put($this->getPath("views/{$viewFolderName}/")."create.blade".".php",$content);
    }

    protected function viewEdit($viewFolderName,$model){
        $create = File::get($this->getPath("commands/crud/template/view/edit.crud"));
        $form = "";
        foreach($this->schema as $fieldName=>$value){
            $formTitle = $this->replaceUnderScore(ucwords($fieldName));
            $arraySearch = explode("|",$value);
            if (in_array("edit",$arraySearch)){
                foreach($this->fieldTypes as $fieldType){
                    if(in_array($fieldType,$arraySearch)){
                        $title  = $this->replaceTitle($fieldName,$formTitle);
                        $fieldName = strtolower($fieldName);
                        $val = $this->variable."->".$fieldName;
                        $element = $this->replaceFieldWithValue($fieldName,$val,$fieldType);
                        $form.= $this->makeHtmlForm($title,$element,$this->updateForm,$arraySearch);
                    }
                }
            }
        }
        $search = array( '{{caption}}', '{{forms}}', '{{variable}}');
        $replace = array($this->caption ,$form ,$this->variable);
        $content = str_replace($search,$replace,$create);
        File::put($this->getPath("views/{$viewFolderName}/")."edit.blade".".php",$content);
    }

    protected function replaceUnderScore($text){
        return str_replace("_"," ",$text);
    }

    protected function replaceTitle($fieldName,$formTitle){
        $search = array('{{id}}', '{{title}}' );
        $replace = array(strtolower($fieldName), $formTitle);
        return str_replace($search,$replace,$this->createForm["title"]);
    }

    protected function replaceField($fieldName,$fieldType){
        $search  = array ( '{{name}}' );
        $replace = array( $fieldName);
        return str_replace($search,$replace,$this->createForm[$fieldType]);
    }

    protected function replaceFieldWithValue($fieldName,$fieldValue,$fieldType){
        $search = array('{{name}}', '{{value}}');
        $replace = array($fieldName, $fieldValue );
        return str_replace($search,$replace,$this->updateForm[$fieldType]);
    }

    protected function newLine(){
        $newLine = "\n ";
        return $newLine;
    }

    protected function tab($number=null){
        $tab = "\t ";
        if($number==null) return $tab;
        $tabs = "";
        for($i=0;$i<$number;$i++){
            $tabs.=$tab;
        }
        return $tabs;
    }

    protected function makeHtmlForm($title,$element,$formArray,$arraySearch){
        $form ="";
        if (in_array("hidden",$arraySearch)){
            $form.= $this->tab()
                .$formArray["div"]
                .$this->newLine()
                .$this->tab(2)
                .$element
                .$this->newLine()
                .$this->tab()
                .$formArray["/div"]
                .$this->newLine();
        }else {
            $form.= $this->tab()
                .$formArray["div"]
                .$this->newLine()
                .$this->tab(2)
                .$title
                .$this->newLine()
                .$this->tab(2)
                .$element
                .$this->newLine()
                .$this->tab()
                .$formArray["/div"]
                .$this->newLine();
        }

        return $form;
    }

}
