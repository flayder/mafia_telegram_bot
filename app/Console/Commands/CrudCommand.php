<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CrudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:create {query}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создает контроллер, модель, вид для crud операций';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    protected function tableToModelName($table) {
        $minmodel = $table;
        if($table[strlen($table)-1] == 's') {
            $minmodel = substr($table, 0, strlen($minmodel)-1);
        }
        $nameparts = explode('_',$minmodel);
        $model = '';
        for($i=0;$i<count($nameparts);$i++) {
            $model .= strtoupper($nameparts[$i][0]).substr($nameparts[$i], 1,strlen($nameparts[$i])-1);
        }
        return $model;
    }
    protected function createModel($table) {
        $model = $this->tableToModelName($table);
        if(file_exists("app/Models/".$model.".php")) return $model;   
        exec("php artisan make:model $model");
        $modelfile = explode("\n",file_get_contents("app/Models/".$model.".php"));
        array_pop($modelfile);
        array_pop($modelfile);
        $modelfile[] = '    protected $table = "'.$table.'";';
        $modelfile[] = '    public static $entityLabel="'.$model.'";';
        $columns = DB::select("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` 
        WHERE `TABLE_SCHEMA`='".env('DB_DATABASE')."'  AND `TABLE_NAME`='$table';");
        $filable = [];
        $labels = [];
        foreach($columns as $column) {
            if($column->COLUMN_NAME =='created_at' || $column->COLUMN_NAME =='updated_at') continue;            
            $cname = $column->COLUMN_NAME;
            $filable[]="'$cname'";
            $labels[] = "'$cname'=>'$cname'";
        }        
        $modelfile[] = '    protected $fillable = ['.implode(",",$filable).'];';
        $modelfile[] = '    public static function labels() {';
        $modelfile[] = '        return ['.implode(',',$labels).'];';
        $modelfile[] = '    }';
        $modelfile[] = '    public static function viewFields() {';
        $modelfile[] = '        return ['.implode(',',$filable).'];';
        $modelfile[] = '    }';
        $modelfile[] = '    public static function headers() {';
        $modelfile[] = '        $labels = self::labels();';
        $modelfile[] = '        $res="";';
        $modelfile[] = '        foreach(self::viewFields() as $field)  {';
        $modelfile[] = '            $res .= "<th>{$labels[$field]}</th>";';
        $modelfile[] = '        }';
        $modelfile[] = '        return $res;';
        $modelfile[] = '    }';
        $modelfile[] = '}';
        file_put_contents("app/Models/".$model.".php",implode("\n",$modelfile));
        return $model;
    }
    protected function createViewList($model,$controller,$list,$listname=null) {
        $lowModel = strtolower($model);
        $listname = $listname ?? $model.'s';
        $viewpath = "resources/views/".strtolower($controller)."/{$list}.blade.php";
        $view_file = ["@extends('layouts.content')","@section('card')",
        "<div class='card-header'>{$listname}</div><div class='card-body'>",
        "<div class='container-fluid'  >",
        "   <form class=row method='get' >",
        '       <div class="col-4"><a class="btn btn-success" href="{{ route(\''.$lowModel.'.create\') }}" >Добавить</a></div>',
        '       <div class="col-2"></div>',
        '        <div class="col-3"><input name="qu" type="search" id="isearch" class="form-control" value="{{ $search ?? null }}" /></div>',
        '        <div class="col-3"><button type="submit" class="btn btn-primary" btnsearch="1" >',
        '           <i class="fas fa-search"></i></button>',
        '       </div>',
        '   </form>',
        '</div>' ];
        $view_file[] = '<table class="table"></div>';
        $view_file[] = '<tr> {!! $modelclass::headers() !!} <td></td></tr>';
        $view_file[] = '@foreach($models as $model)';
        $view_file[] = '<tr>';
        $view_file[] =' @foreach($modelclass::viewFields() as $field)';
        $view_file[] =' <td>{{ $model->$field }}</td>';
        $view_file[] =' @endforeach';
        $view_file[] = '<td> <a href="{{ route(\''.$lowModel.'.update\',[\'id\'=>$model->id]) }}"><i class="fas fa-pen"></i></a>
        
        <a href="javascript:void()" onclick="openRemoveDialog(\'{{ route(\''.$lowModel.'.delete\') }}\',{{ $model->id }},\'{{ $modelclass::$entityLabel }}\')" ><i class="far fa-trash-alt"></i></a>        
        </td>';
        
        $view_file[] = '</tr>';
        $view_file[] ='@endforeach';
        $view_file[] = '</table>';
        $view_file[] = '{{ $models->links() }}';
        $view_file[] = '</div>';
        $view_file[]='@endsection';
        file_put_contents($viewpath, implode("\n",$view_file));
        //добавим в меню
        file_put_contents("resources/views/layouts/menu.blade.php",
        "\n".'<a class="nav-link {{ request()->routeIs(\''.$list.'\') ? \'active\' : \'\' }}" href="{{ route(\''.$list.'\') }}">'.$listname.'</a>',
        FILE_APPEND);
    }
    protected function createViewForm($controller,$create,$modelclass) {
        $viewpath = "resources/views/".strtolower($controller)."/{$create}.blade.php";
        $view_file = ["@extends('layouts.content')","@section('card')",
        '<div class="card-header">{{ $title }}</div><div class="card-body">'];
        $view_file[] = "<form method='POST' enctype='multipart/form-data' >";
        $view_file[] = "@csrf";
        //$view_file[] = "<table>";
        foreach($modelclass::viewFields() as $field) {
            $view_file[] = '<div class="form-group">';
            $view_file[] = "<label for='$field'>{{ $modelclass::labels()['$field'] }}</label>";
            if(strpos($field,'_id')!==false) {
                $view_file[] = "<select class='form-control form-select' id='$field' name='$field' >";
                $view_file[] ="{!! \\App\\Modules\\Functions::comboOptions(\${$field}s,'id','name',\$model->$field) !!}";
                $view_file[] = "</select>";
            }
            else if(strpos($field,'is_')!==false) {
                $fieldLabel = array_pop($view_file);
                $view_file[] ='<div class="form-check">';
                $view_file[] ="<input class='form-check-input' type='checkbox' value='1' name='$field' id='$field' @if(\$model->$field == 1) checked @endif>";
                $view_file[] = $fieldLabel;    
                $view_file[] = "</div>";
            }
            else if($field == 'image' || $field == 'preview' || $field=='icon') {
                $view_file[] = '@if($model->'.$field.')  <img style="max-width:100%" src="{{ $model->'.$field.' }}" > @endif';
                $view_file[] = "<input type='file' class='form-control' id='$field' name='$field'  >";
            }
            else if($field == 'video') {
                $view_file[] = '@if($model->'.$field.')  <video style="max-width:100%" src="{{ $model->'.$field.' }}" ></video> @endif';
                $view_file[] = "<input type='file' class='form-control' id='$field' name='$field'  >";
            }
            else {
                $view_file[] = "<input class='form-control @error(\"{$field}\") is-invalid @enderror' id='$field' name='$field' value='{{ \$model->$field }}' >";
            }
            $view_file[] = "</div>";
            //$view_file[] = "<tr><td>{{ $modelclass::labels()['$field'] }}: </td><td>".            
            //'<input name="'.$field.'" type="text" class="@error(\''.$field.'\') is-invalid @enderror"></td></tr>';
        }        
        //$view_file[] = "<tr><td><input type='submit' value='Сохранить' ></td><td></td></tr>";
        //$view_file[] = "</table>";
        $view_file[] = '<button type="submit" class="btn btn-primary">Сохранить</button>';
        $view_file[] = "</form>";
        $view_file[]='@endsection';
        file_put_contents($viewpath, implode("\n",$view_file));
    }
    protected function createController($model, $controller=null,$listname=null) {
        $controller = $controller ?? $model;
        $cname = "{$controller}Controller";
        if(!file_exists("app/Http/Controllers/{$cname}.php")) {
            exec("php artisan make:controller $cname");
        }
        $cntr_file = explode("\n",file_get_contents("app/Http/Controllers/{$cname}.php"));
        array_pop($cntr_file); array_pop($cntr_file); //убрали две последних строки
        echo "получили файл контроллера...";
        $lowModel = strtolower($model);
        $list = $lowModel."List";
        $create = $lowModel."Create";
        $update = $lowModel."Update";
        $delete = $lowModel."Delete";
        $form = $lowModel."Form";
        $modelclass = "\\App\\Models\\$model";
        //список моделей
        $cntr_file[]="  public function {$list}() {";
        $cntr_file[]='      $data["modelclass"] = "'.$modelclass.'";';
        $cntr_file[]='      $search = $_GET["qu"] ?? null;';
        $cntr_file[]='      if($search) {';
        $cntr_file[]='          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);';
        $cntr_file[]='          $data["search"] = $search;';
        $cntr_file[]='      }';
        $cntr_file[]='      else $data["models"] = ($data["modelclass"])::paginate(15);';
        $cntr_file[]="      return view('".strtolower($controller).".{$list}',".'$data'.");";
        $cntr_file[]="  }";
        //создание новой записи
        $cntr_file[]="  public function {$create}(Request \$request) {";
        $cntr_file[]='      if($_POST) {';
        $cntr_file[]='          $model = '.$modelclass.'::create($_POST);';
        $isCheckUpd = 0;
        foreach($modelclass::viewFields() as $viewField) {
            if(strpos($viewField,'is_')!==false) {
                $cntr_file[]="          \$model->$viewField = \$request->has('$viewField') ? \$request->input('$viewField') : 0;";            
                $isCheckUpd++;
            }            
        }
        if($isCheckUpd) $cntr_file[]="          \$model->save();";            
        $pictures = ['image','preview','icon','video'];
        foreach($pictures as $picture) {
            if(in_array($picture,$modelclass::viewFields())) {
                $cntr_file[]="          $$picture = \$this->upload(\$request,'$picture');";
                $cntr_file[]="          if($$picture) {";
                $cntr_file[]="              \$model->$picture = \${$picture}[0];";
                $cntr_file[]="              \$model->save();";
                $cntr_file[]="          }";                
            }
        }        
        $cntr_file[]="          return redirect(route('$list'));";
        $cntr_file[]='      }';
        $cntr_file[]='      $data["model"] = new '.$modelclass.'();';
        $cntr_file[]='      $data["modelclass"] = "'.$modelclass.'";';
        $cntr_file[]='      $data["title"] = "'.$model.' Create";';
        foreach($modelclass::labels() as $k=>$v) {
            if(strpos($k,'_id')!==false) {
                $kidModelArr = explode('_',$k);
                $kidModel = $this->tableToModelName($kidModelArr[0]);
                $cntr_file[]='      $data["'.$k.'s"] = \\App\\Models\\'.$kidModel.'::all();';
            }
        }
        $cntr_file[]="      return view('".strtolower($controller).".{$form}',".'$data);';
        $cntr_file[]='  }'; //метод        
        //обновление записи
        $cntr_file[]="  public function {$update}(Request \$request,\$id) {";
        $cntr_file[]='      $data["model"] = '.$modelclass.'::where("id",$id)->first();';
        $cntr_file[]='      if(!$data["model"]) return redirect(route("'.$list.'"));';
        $cntr_file[]='      if($_POST) {';
        $cntr_file[]='          $data["model"]->fill($_POST);';
        $pictures = ['image','preview','icon','video'];
        foreach($modelclass::viewFields() as $viewField) {
            if(strpos($viewField,'is_')!==false) $cntr_file[]="          \$data['model']->$viewField = \$request->has('$viewField') ? \$request->input('$viewField') : 0;";            
        }
        foreach($pictures as $picture) {
            if(in_array($picture,$modelclass::viewFields())) {
                $cntr_file[]="          $$picture = \$this->upload(\$request,'$picture');";
                $cntr_file[]="          if($$picture) {";
                $cntr_file[]="              \$data['model']->$picture = \${$picture}[0];";                
                $cntr_file[]="          }";                
            }
        }
        $cntr_file[]='          $data["model"]->save();';
        $cntr_file[]="          return redirect(route('{$list}'));";
        $cntr_file[]='      }';        
        $cntr_file[]='      $data["modelclass"] = "'.$modelclass.'";';
        $cntr_file[]='      $data["title"] = "'.$model.' Edit";';
        foreach($modelclass::labels() as $k=>$v) {
            if(strpos($k,'_id')!==false) {
                $kidModelArr = explode('_',$k);
                $kidModel = $this->tableToModelName($kidModelArr[0]);
                $cntr_file[]='      $data["'.$k.'s"] = \\App\\Models\\'.$kidModel.'::all();';
            }
        }
        $cntr_file[]="      return view('".strtolower($controller).".{$form}',".'$data);';
        $cntr_file[]='  }'; //метод
        //удаление записи
        $cntr_file[]="  public function {$delete}() {";
        $cntr_file[]='    $model='.$modelclass.'::where("id",$_POST["id"])->first();';
        $cntr_file[]='    $model->delete();';
        $cntr_file[]="    return redirect(route('{$list}'));";
        $cntr_file[]='  }'; //метод
        $cntr_file[]="\n}"; //класс
        echo "\nметоды CRUD в контроллере готовы.\n";
        //дописываем в web
        $web_file = explode("\n",file_get_contents("routes/web.php"));
        $web_file[] = "Route::get('/".$lowModel."s', [App\\Http\\Controllers\\{$cname}::class, '$list'])->name('$list');";
        $web_file[] = "Route::any('/".$lowModel."/create', [App\\Http\\Controllers\\{$cname}::class, '$create'])->name('$lowModel.create');";
        $web_file[] = "Route::any('/".$lowModel."/update/{id}', [App\\Http\\Controllers\\{$cname}::class, '$update'])->name('$lowModel.update');";
        $web_file[] = "Route::post('/".$lowModel."/delete', [App\\Http\\Controllers\\{$cname}::class, '$delete'])->name('$lowModel.delete');";
        //создаем view 
        if(!file_exists("resources/views/".strtolower($controller))) {
            mkdir("resources/views/".strtolower($controller));
        }
        $this->createViewList($model,$controller,$list,$listname);
        $this->createViewForm($controller,$form,$modelclass);
        
        file_put_contents("app/Http/Controllers/{$cname}.php",implode("\n",$cntr_file));
        file_put_contents("routes/web.php",implode("\n",$web_file));        
    }
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    { //query = table~controller~listname
        $query = $this->argument('query');
        $arr_query = explode('~',$query);              
        $model = $this->createModel($arr_query[0]);
        echo "model created";
        $this->createController($model,(empty($arr_query[1]) ? null : $arr_query[1]),(empty($arr_query[2]) ? null : $arr_query[2]));
        echo "controller created\n";

        return 0;
    }
}
