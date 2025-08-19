<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Voiting;
use App\Models\GameUser;
use App\Modules\Functions;
use App\Modules\Game\Game;
use App\Modules\Bot\AppBot;
use App\Modules\FileUpload;
use Illuminate\Http\Request;
use App\Models\Game as GameModel;
use App\Models\GameRole;
use App\Models\GamerParam;
use App\Models\GroupTarif;
use App\Modules\Game\Currency;
use PHPUnit\TextUI\XmlConfiguration\Group;

// CREATE USER phpmyadmin@localhost IDENTIFIED BY 'TwR324dfWrtfhft58#gh';
//GRANT ALL PRIVILEGES ON *.* TO phpmyadmin@localhost;
// CREATE USER mafiya@localhost IDENTIFIED BY 'wR324dfWrtfhft58#gh'
//GRANT ALL PRIVILEGES ON mafiya_bot.* TO mafiya@localhost  IDENTIFIED BY 'wR324dfWrtfhft58#gh';
class AdminController extends Controller
{
    use FileUpload;
    //
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('access');
    }
    public function index()
    {
        return view('layouts.content');
    }

  public function botuserList() {
      $data["modelclass"] = "\App\Models\BotUser";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("id","like","%{$search}%")->orWhere("nick_name","like","%{$search}%")
          ->orWhere("first_name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.botuserList',$data);
  }
  public function botuserCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\BotUser::create($_POST);
          return redirect(route('botuserList'));
      }
      $data["model"] = new \App\Models\BotUser();
      $data["modelclass"] = "\App\Models\BotUser";
      $data["title"] = "BotUser Create";
      return view('admin.botuserForm',$data);
  }
  public function botuserUpdate(Request $request,$id) {
      $data["model"] = \App\Models\BotUser::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("botuserList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('botuserList'));
      }
      $data["modelclass"] = "\App\Models\BotUser";
      $data["title"] = "BotUser Edit";
      return view('admin.botuserForm',$data);
  }
  public function botuserDelete() {
    $model=\App\Models\BotUser::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('botuserList'));
  }  
  public function currencyList() {
      $data["modelclass"] = "\App\Models\Currency";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.currencyList',$data);
  }
  public function currencyCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\Currency::create($_POST);
          $model->is_season = $request->has('is_season') ? $request->input('is_season') : 0;
          $model->save();
          return redirect(route('currencyList'));
      }
      $data["model"] = new \App\Models\Currency();
      $data["modelclass"] = "\App\Models\Currency";
      $data["title"] = "Currency Create";
      return view('admin.currencyForm',$data);
  }
  public function currencyUpdate(Request $request,$id) {
      $data["model"] = \App\Models\Currency::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("currencyList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data['model']->is_season = $request->has('is_season') ? $request->input('is_season') : 0;
          $data["model"]->save();
          return redirect(route('currencyList'));
      }
      $data["modelclass"] = "\App\Models\Currency";
      $data["title"] = "Currency Edit";
      return view('admin.currencyForm',$data);
  }
  public function currencyDelete() {
    $model=\App\Models\Currency::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('currencyList'));
  }
  public function botgroupList() {
      $data["modelclass"] = "\App\Models\BotGroup";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.botgroupList',$data);
  }
  public function botgroupCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\BotGroup::create($_POST);
          return redirect(route('botgroupList'));
      }
      $data["model"] = new \App\Models\BotGroup();
      $data["modelclass"] = "\App\Models\BotGroup";
      $data["title"] = "BotGroup Create";
      return view('admin.botgroupForm',$data);
  }
  public function botgroupUpdate(Request $request,$id) {
      $data["model"] = \App\Models\BotGroup::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("botgroupList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('botgroupList'));
      }
      $data["modelclass"] = "\App\Models\BotGroup";
      $data["title"] = "BotGroup Edit";
      return view('admin.botgroupForm',$data);
  }
  public function botgroupDelete() {
    $model=\App\Models\BotGroup::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('botgroupList'));
  }
  public function sendcurhistoryList() {
      $data["modelclass"] = "\App\Models\SendCurHistory";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.sendcurhistoryList',$data);
  }
  public function sendcurhistoryCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\SendCurHistory::create($_POST);
          return redirect(route('sendcurhistoryList'));
      }
      $data["model"] = new \App\Models\SendCurHistory();
      $data["modelclass"] = "\App\Models\SendCurHistory";
      $data["title"] = "SendCurHistory Create";
      $data["group_ids"] = \App\Models\BotGroup::all();
      return view('admin.sendcurhistoryForm',$data);
  }
  public function sendcurhistoryUpdate(Request $request,$id) {
      $data["model"] = \App\Models\SendCurHistory::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("sendcurhistoryList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('sendcurhistoryList'));
      }
      $data["modelclass"] = "\App\Models\SendCurHistory";
      $data["title"] = "SendCurHistory Edit";
      $data["group_ids"] = \App\Models\BotGroup::all();
      return view('admin.sendcurhistoryForm',$data);
  }
  public function sendcurhistoryDelete() {
    $model=\App\Models\SendCurHistory::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('sendcurhistoryList'));
  }
  public function chatmemberList() {
      $data["modelclass"] = "\App\Models\ChatMember";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.chatmemberList',$data);
  }
  public function chatmemberCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\ChatMember::create($_POST);
          $model->is_bot = $request->has('is_bot') ? $request->input('is_bot') : 0;
          $model->is_premium = $request->has('is_premium') ? $request->input('is_premium') : 0;
          $model->save();
          return redirect(route('chatmemberList'));
      }
      $data["model"] = new \App\Models\ChatMember();
      $data["modelclass"] = "\App\Models\ChatMember";
      $data["title"] = "ChatMember Create";
      $data["member_ids"] = \App\Models\ChatMember::all();
      $data["group_ids"] = \App\Models\BotGroup::all();
      return view('admin.chatmemberForm',$data);
  }
  public function chatmemberUpdate(Request $request,$id) {
      $data["model"] = \App\Models\ChatMember::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("chatmemberList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data['model']->is_bot = $request->has('is_bot') ? $request->input('is_bot') : 0;
          $data['model']->is_premium = $request->has('is_premium') ? $request->input('is_premium') : 0;
          $data["model"]->save();
          return redirect(route('chatmemberList'));
      }
      $data["modelclass"] = "\App\Models\ChatMember";
      $data["title"] = "ChatMember Edit";
      $data["member_ids"] = \App\Models\ChatMember::all();
      $data["group_ids"] = \App\Models\BotGroup::all();
      return view('admin.chatmemberForm',$data);
  }
  public function chatmemberDelete() {
    $model=\App\Models\ChatMember::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('chatmemberList'));
  }
  public function roletypeList() {
      $data["modelclass"] = "\App\Models\RoleType";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.roletypeList',$data);
  }
  public function roletypeCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\RoleType::create($_POST);
          return redirect(route('roletypeList'));
      }
      $data["model"] = new \App\Models\RoleType();
      $data["modelclass"] = "\App\Models\RoleType";
      $data["title"] = "RoleType Create";
      return view('admin.roletypeForm',$data);
  }
  public function roletypeUpdate(Request $request,$id) {
      $data["model"] = \App\Models\RoleType::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("roletypeList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('roletypeList'));
      }
      $data["modelclass"] = "\App\Models\RoleType";
      $data["title"] = "RoleType Edit";
      return view('admin.roletypeForm',$data);
  }
  public function roletypeDelete() {
    $model=\App\Models\RoleType::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('roletypeList'));
  }
  public function gameroleList() {
      $data["modelclass"] = "\App\Models\GameRole";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.gameroleList',$data);
  }
  public function gameroleCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\GameRole::create($_POST);
          $model->is_select_partner = $request->has('is_select_partner') ? $request->input('is_select_partner') : 0;
          $model->save();
          return redirect(route('gameroleList'));
      }
      $data["model"] = new \App\Models\GameRole();
      $data["modelclass"] = "\App\Models\GameRole";
      $data["title"] = "GameRole Create";
      $data["role_type_ids"] = \App\Models\RoleType::all();
      return view('admin.gameroleForm',$data);
  }
  public function gameroleUpdate(Request $request,$id) {
      $data["model"] = \App\Models\GameRole::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("gameroleList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data['model']->is_select_partner = $request->has('is_select_partner') ? $request->input('is_select_partner') : 0;
          $data["model"]->save();
          return redirect(route('gameroleList'));
      }
      $data["modelclass"] = "\App\Models\GameRole";
      $data["title"] = "GameRole Edit";
      $data["role_type_ids"] = \App\Models\RoleType::all();
      return view('admin.gameroleForm',$data);
  }
  public function gameroleDelete() {
    $model=\App\Models\GameRole::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('gameroleList'));
  }  
  public function gamerolesorderList() {
      $data["modelclass"] = "\App\Models\GameRolesOrder";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.gamerolesorderList',$data);
  }
  public function gamerolesorderCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\GameRolesOrder::create($_POST);
          return redirect(route('gamerolesorderList'));
      }
      $data["model"] = new \App\Models\GameRolesOrder();
      $data["modelclass"] = "\App\Models\GameRolesOrder";
      $data["title"] = "GameRolesOrder Create";
      $data["role_ids"] = \App\Models\GameRole::all();
      return view('admin.gamerolesorderForm',$data);
  }
  public function gamerolesorderUpdate(Request $request,$id) {
      $data["model"] = \App\Models\GameRolesOrder::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("gamerolesorderList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('gamerolesorderList'));
      }
      $data["modelclass"] = "\App\Models\GameRolesOrder";
      $data["title"] = "GameRolesOrder Edit";
      $data["role_ids"] = \App\Models\GameRole::all();
      return view('admin.gamerolesorderForm',$data);
  }
  public function gamerolesorderDelete() {
    $model=\App\Models\GameRolesOrder::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('gamerolesorderList'));
  }
  public function taskList() {
      $data["modelclass"] = "\App\Models\Task";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.taskList',$data);
  }
  public function taskCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\Task::create($_POST);
          $model->is_active = $request->has('is_active') ? $request->input('is_active') : 0;
          $model->save();
          return redirect(route('taskList'));
      }
      $data["model"] = new \App\Models\Task();
      $data["modelclass"] = "\App\Models\Task";
      $data["title"] = "Task Create";
      return view('admin.taskForm',$data);
  }
  public function taskUpdate(Request $request,$id) {
      $data["model"] = \App\Models\Task::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("taskList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data['model']->is_active = $request->has('is_active') ? $request->input('is_active') : 0;
          $data["model"]->save();
          return redirect(route('taskList'));
      }
      $data["modelclass"] = "\App\Models\Task";
      $data["title"] = "Task Edit";
      return view('admin.taskForm',$data);
  }
  public function taskDelete() {
    $model=\App\Models\Task::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('taskList'));
  }
  public function settingList() {
      $data["modelclass"] = "\App\Models\Setting";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("title","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.settingList',$data);
  }
  public function settingCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\Setting::create($_POST);
          return redirect(route('settingList'));
      }
      $data["model"] = new \App\Models\Setting();
      $data["modelclass"] = "\App\Models\Setting";
      $data["title"] = "Setting Create";
      $data['tarif_ids'] = GroupTarif::all();
      return view('admin.settingForm',$data);
  }
  public function settingUpdate(Request $request,$id) {
      $data["model"] = \App\Models\Setting::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("settingList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('settingList'));
      }
      $data["modelclass"] = "\App\Models\Setting";
      $data["title"] = "Setting Edit";
      $data['tarif_ids'] = GroupTarif::all();
      return view('admin.settingForm',$data);
  }
  public function settingDelete() {
    $model=\App\Models\Setting::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('settingList'));
  }
  public function voitingList() {
      $data["modelclass"] = "\App\Models\Voiting";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.voitingList',$data);
  }
  public function voitingCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\Voiting::create($_POST);
          $model->is_active = $request->has('is_active') ? $request->input('is_active') : 0;
          $model->save();
          return redirect(route('voitingList'));
      }
      $data["model"] = new \App\Models\Voiting();
      $data["modelclass"] = "\App\Models\Voiting";
      $data["title"] = "Voiting Create";
      $data["game_ids"] = \App\Models\Game::all();
      return view('admin.voitingForm',$data);
  }
  public function voitingUpdate(Request $request,$id) {
      $data["model"] = \App\Models\Voiting::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("voitingList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data['model']->is_active = $request->has('is_active') ? $request->input('is_active') : 0;
          $data["model"]->save();
          return redirect(route('voitingList'));
      }
      $data["modelclass"] = "\App\Models\Voiting";
      $data["title"] = "Voiting Edit";
      $data["game_ids"] = \App\Models\Game::all();
      return view('admin.voitingForm',$data);
  }
  public function voitingDelete() {
    $model=\App\Models\Voiting::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('voitingList'));
  }
  public function voteList() {
      $data["modelclass"] = "\App\Models\Vote";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.voteList',$data);
  }
  public function voteCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\Vote::create($_POST);
          return redirect(route('voteList'));
      }
      $data["model"] = new \App\Models\Vote();
      $data["modelclass"] = "\App\Models\Vote";
      $data["title"] = "Vote Create";
      $data["voiting_ids"] = \App\Models\Voiting::all();
      $data["gamer_ids"] = \App\Models\GameUser::all();
      $data["vote_user_ids"] = \App\Models\Vote::all();
      return view('admin.voteForm',$data);
  }
  public function voteUpdate(Request $request,$id) {
      $data["model"] = \App\Models\Vote::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("voteList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('voteList'));
      }
      $data["modelclass"] = "\App\Models\Vote";
      $data["title"] = "Vote Edit";
      $data["voiting_ids"] = \App\Models\Voiting::all();
      $data["gamer_ids"] = \App\Models\GameUser::all();
      $data["vote_user_ids"] = \App\Models\Vote::all();
      return view('admin.voteForm',$data);
  }
  public function voteDelete() {
    $model=\App\Models\Vote::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('voteList'));
  }
  public function yesnovoteList() {
      $data["modelclass"] = "\App\Models\YesnoVote";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.yesnovoteList',$data);
  }
  public function yesnovoteCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\YesnoVote::create($_POST);
          return redirect(route('yesnovoteList'));
      }
      $data["model"] = new \App\Models\YesnoVote();
      $data["modelclass"] = "\App\Models\YesnoVote";
      $data["title"] = "YesnoVote Create";
      $data["voiting_ids"] = \App\Models\Voiting::all();
      $data["gamer_ids"] = \App\Models\GameUser::all();
      $data["vote_user_ids"] = \App\Models\Vote::all();
      return view('admin.yesnovoteForm',$data);
  }
  public function yesnovoteUpdate(Request $request,$id) {
      $data["model"] = \App\Models\YesnoVote::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("yesnovoteList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('yesnovoteList'));
      }
      $data["modelclass"] = "\App\Models\YesnoVote";
      $data["title"] = "YesnoVote Edit";
      $data["voiting_ids"] = \App\Models\Voiting::all();
      $data["gamer_ids"] = \App\Models\GameUser::all();
      $data["vote_user_ids"] = \App\Models\Vote::all();
      return view('admin.yesnovoteForm',$data);
  }
  public function yesnovoteDelete() {
    $model=\App\Models\YesnoVote::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('yesnovoteList'));
  }
  public function generateAchives() {
    $words = [10=>'Неуловимый',30=>'Непокоримый',
    50=>'Мастер',100=>'Гуру',200=>'Легенда',
    500=>'Магистр',1000=>'Элита'];
    $roles = GameRole::all();
    foreach($roles as $role) {
        foreach($words as $k=>$v) {
            Achievement::create(['name'=>$v.' '.$role->name,'role_id'=>$role->id,'win_amount'=>$k]);
        }        
    }
    echo "generateAchives - OK";
  }
  public function test() {
    echo "<pre>";
    $this->generateAchives();
    /*
    $game_id = 6;
    $game = GameModel::where('id',$game_id)->first();
    $res = Game::isGameOver($game_id);
    Game::stopGame($game, $res);
    print_r($res);
    */

  }
  public function rolesneedfromsaveList() {
      $data["modelclass"] = "\App\Models\RolesNeedFromSave";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $roles = GameRole::where('name','like',"%{$search}%")->get()->all();
          $roleIds = array_column($roles,'id'); //'role_id','saved_role_id'
          $data["models"] = ($data["modelclass"])::whereIn("role_id",$roleIds)->orWhereIn("saved_role_id",$roleIds)->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.rolesneedfromsaveList',$data);
  }
  public function rolesneedfromsaveCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\RolesNeedFromSave::create($_POST);
          return redirect(route('rolesneedfromsaveList'));
      }
      $data["model"] = new \App\Models\RolesNeedFromSave();
      $data["modelclass"] = "\App\Models\RolesNeedFromSave";
      $data["title"] = "RolesNeedFromSave Create";
      $data["role_ids"] = \App\Models\GameRole::all();
      $data["saved_role_ids"] = \App\Models\GameRole::all();
      return view('admin.rolesneedfromsaveForm',$data);
  }
  public function rolesneedfromsaveUpdate(Request $request,$id) {
      $data["model"] = \App\Models\RolesNeedFromSave::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("rolesneedfromsaveList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('rolesneedfromsaveList'));
      }
      $data["modelclass"] = "\App\Models\RolesNeedFromSave";
      $data["title"] = "RolesNeedFromSave Edit";
      $data["role_ids"] = \App\Models\GameRole::all();
      $data["saved_role_ids"] = \App\Models\GameRole::all();
      return view('admin.rolesneedfromsaveForm',$data);
  }
  public function rolesneedfromsaveDelete() {
    $model=\App\Models\RolesNeedFromSave::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('rolesneedfromsaveList'));
  }
  public function sleepkillroleList() {
      $data["modelclass"] = "\App\Models\SleepKillRole";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.sleepkillroleList',$data);
  }
  public function sleepkillroleCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\SleepKillRole::create($_POST);
          $model->is_one = $request->has('is_one') ? $request->input('is_one') : 0;
          $model->save();
          return redirect(route('sleepkillroleList',['page'=>ceil($model->id/15)]));
      }
      $data["model"] = new \App\Models\SleepKillRole();
      $data["modelclass"] = "\App\Models\SleepKillRole";
      $data["title"] = "SleepKillRole Create";
      $data["role_ids"] = \App\Models\GameRole::all();
      return view('admin.sleepkillroleForm',$data);
  }
  public function sleepkillroleUpdate(Request $request,$id) {
      $data["model"] = \App\Models\SleepKillRole::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("sleepkillroleList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data['model']->is_one = $request->has('is_one') ? $request->input('is_one') : 0;
          $data["model"]->save();
          return redirect(route('sleepkillroleList'));
      }
      $data["modelclass"] = "\App\Models\SleepKillRole";
      $data["title"] = "SleepKillRole Edit";
      $data["role_ids"] = \App\Models\GameRole::all();
      return view('admin.sleepkillroleForm',$data);
  }
  public function sleepkillroleDelete() {
    $model=\App\Models\SleepKillRole::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('sleepkillroleList'));
  }
  public function bafList() {
      $data["modelclass"] = "\App\Models\Baf";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.bafList',$data);
  }
  public function bafCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\Baf::create($_POST);
          return redirect(route('bafList'));
      }
      $data["model"] = new \App\Models\Baf();
      $data["modelclass"] = "\App\Models\Baf";
      $data["title"] = "Baf Create";
      $data['currencies'] = Currency::allCurrencies();

      return view('admin.bafForm',$data);
  }
  public function bafUpdate(Request $request,$id) {
      $data["model"] = \App\Models\Baf::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("bafList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('bafList'));
      }
      $data["modelclass"] = "\App\Models\Baf";
      $data["title"] = "Baf Edit";
      $data['currencies'] = Currency::allCurrencies();
      return view('admin.bafForm',$data);
  }
  public function bafDelete() {
    $model=\App\Models\Baf::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('bafList'));
  }
  public function achievementList() {
      $data["modelclass"] = "\App\Models\Achievement";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.achievementList',$data);
  }
  public function achievementCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\Achievement::create($_POST);
          return redirect(route('achievementList'));
      }
      $data["model"] = new \App\Models\Achievement();
      $data["modelclass"] = "\App\Models\Achievement";
      $data["title"] = "Achievement Create";
      $data["role_ids"] = \App\Models\GameRole::all();
      return view('admin.achievementForm',$data);
  }
  public function achievementUpdate(Request $request,$id) {
      $data["model"] = \App\Models\Achievement::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("achievementList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('achievementList'));
      }
      $data["modelclass"] = "\App\Models\Achievement";
      $data["title"] = "Achievement Edit";
      $data["role_ids"] = \App\Models\GameRole::all();
      return view('admin.achievementForm',$data);
  }
  public function achievementDelete() {
    $model=\App\Models\Achievement::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('achievementList'));
  }
  public function productList() {
      $data["modelclass"] = "\App\Models\Product";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.productList',$data);
  }
  public function productCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\Product::create($_POST);
          return redirect(route('productList'));
      }
      $data["model"] = new \App\Models\Product();
      $data["modelclass"] = "\App\Models\Product";
      $data["title"] = "Product Create";
      $data['currencies'] = Currency::allCurrencies();
      return view('admin.productForm',$data);
  }
  public function productUpdate(Request $request,$id) {
      $data["model"] = \App\Models\Product::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("productList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('productList'));
      }
      $data["modelclass"] = "\App\Models\Product";
      $data["title"] = "Product Edit";
      $data['currencies'] = Currency::allCurrencies();
      return view('admin.productForm',$data);
  }
  public function productDelete() {
    $model=\App\Models\Product::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('productList'));
  }
  public function warningtypeList() {
      $data["modelclass"] = "\App\Models\WarningType";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.warningtypeList',$data);
  }
  public function warningtypeCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\WarningType::create($_POST);
          $model->is_mute = $request->has('is_mute') ? $request->input('is_mute') : 0;
          $model->save();
          return redirect(route('warningtypeList'));
      }
      $data["model"] = new \App\Models\WarningType();
      $data["modelclass"] = "\App\Models\WarningType";
      $data["title"] = "WarningType Create";
      return view('admin.warningtypeForm',$data);
  }
  public function warningtypeUpdate(Request $request,$id) {
      $data["model"] = \App\Models\WarningType::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("warningtypeList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data['model']->is_mute = $request->has('is_mute') ? $request->input('is_mute') : 0;
          $data["model"]->save();
          return redirect(route('warningtypeList'));
      }
      $data["modelclass"] = "\App\Models\WarningType";
      $data["title"] = "WarningType Edit";
      return view('admin.warningtypeForm',$data);
  }
  public function warningtypeDelete() {
    $model=\App\Models\WarningType::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('warningtypeList'));
  }
  public function warningwordList() {
      $data["modelclass"] = "\App\Models\WarningWord";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.warningwordList',$data);
  }
  public function warningwordCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\WarningWord::create($_POST);
          return redirect(route('warningwordList'));
      }
      $data["model"] = new \App\Models\WarningWord();
      $data["modelclass"] = "\App\Models\WarningWord";
      $data["title"] = "WarningWord Create";
      $data["group_ids"] = \App\Models\BotGroup::all();
      return view('admin.warningwordForm',$data);
  }
  public function warningwordUpdate(Request $request,$id) {
      $data["model"] = \App\Models\WarningWord::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("warningwordList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('warningwordList'));
      }
      $data["modelclass"] = "\App\Models\WarningWord";
      $data["title"] = "WarningWord Edit";
      $data["group_ids"] = \App\Models\BotGroup::all();
      return view('admin.warningwordForm',$data);
  }
  public function warningwordDelete() {
    $model=\App\Models\WarningWord::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('warningwordList'));
  }
  public function roleactionList() {
      $data["modelclass"] = "\App\Models\RoleAction";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("action","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.roleactionList',$data);
  }
  public function roleactionCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\RoleAction::create($_POST);
          return redirect(route('roleactionList'));
      }
      $data["model"] = new \App\Models\RoleAction();
      $data["modelclass"] = "\App\Models\RoleAction";
      $data["title"] = "RoleAction Create";
      $data["role_ids"] = \App\Models\GameRole::all();
      return view('admin.roleactionForm',$data);
  }
  public function roleactionUpdate(Request $request,$id) {
      $data["model"] = \App\Models\RoleAction::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("roleactionList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('roleactionList'));
      }
      $data["modelclass"] = "\App\Models\RoleAction";
      $data["title"] = "RoleAction Edit";
      $data["role_ids"] = \App\Models\GameRole::all();
      return view('admin.roleactionForm',$data);
  }
  public function roleactionDelete() {
    $model=\App\Models\RoleAction::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('roleactionList'));
  }
  public function buyroleList() {
      $data["modelclass"] = "\App\Models\BuyRole";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.buyroleList',$data);
  }
  public function buyroleCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\BuyRole::create($_POST);
          return redirect(route('buyroleList'));
      }
      $data["model"] = new \App\Models\BuyRole();
      $data["modelclass"] = "\App\Models\BuyRole";
      $data["title"] = "BuyRole Create";
      $data["role_ids"] = \App\Models\GameRole::all();
      $data['currencies'] = Currency::allCurrencies();
      return view('admin.buyroleForm',$data);
  }
  public function buyroleUpdate(Request $request,$id) {
      $data["model"] = \App\Models\BuyRole::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("buyroleList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('buyroleList'));
      }
      $data["modelclass"] = "\App\Models\BuyRole";
      $data["title"] = "BuyRole Edit";
      $data["role_ids"] = \App\Models\GameRole::all();
      $data['currencies'] = Currency::allCurrencies();
      return view('admin.buyroleForm',$data);
  }
  public function buyroleDelete() {
    $model=\App\Models\BuyRole::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('buyroleList'));
  }  
  public function offerList() {
      $data["modelclass"] = "\App\Models\Offer";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.offerList',$data);
  }
  public function offerCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\Offer::create($_POST);          
          return redirect(route('offerList',['page'=>ceil($model->id / 15)]));
      }
      $data["model"] = new \App\Models\Offer();
      $data["modelclass"] = "\App\Models\Offer";
      $data["title"] = "Offer Create";
      $data["parent_ids"] = \App\Models\Offer::all();
      return view('admin.offerForm',$data);
  }
  public function offerUpdate(Request $request,$id) {
      $data["model"] = \App\Models\Offer::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("offerList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('offerList',['page'=>ceil($data["model"]->id / 15)]));
      }
      $data["modelclass"] = "\App\Models\Offer";
      $data["title"] = "Offer Edit";
      $data["parent_ids"] = \App\Models\Offer::all();
      return view('admin.offerForm',$data);
  }
  public function offerDelete() {
    $model=\App\Models\Offer::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('offerList'));
  }
  public function currencyrateList() {
      $data["modelclass"] = "\App\Models\CurrencyRate";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.currencyrateList',$data);
  }
  public function currencyrateCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\CurrencyRate::create($_POST);
          return redirect(route('currencyrateList'));
      }
      $data["model"] = new \App\Models\CurrencyRate();
      $data["modelclass"] = "\App\Models\CurrencyRate";
      $data["title"] = "CurrencyRate Create";
      return view('admin.currencyrateForm',$data);
  }
  public function currencyrateUpdate(Request $request,$id) {
      $data["model"] = \App\Models\CurrencyRate::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("currencyrateList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('currencyrateList'));
      }
      $data["modelclass"] = "\App\Models\CurrencyRate";
      $data["title"] = "CurrencyRate Edit";
      return view('admin.currencyrateForm',$data);
  }
  public function currencyrateDelete() {
    $model=\App\Models\CurrencyRate::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('currencyrateList'));
  }
  public function grouptarifList() {
      $data["modelclass"] = "\App\Models\GroupTarif";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.grouptarifList',$data);
  }
  public function grouptarifCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\GroupTarif::create($_POST);
          return redirect(route('grouptarifList'));
      }
      $data["model"] = new \App\Models\GroupTarif();
      $data["modelclass"] = "\App\Models\GroupTarif";
      $data["title"] = "GroupTarif Create";      
      return view('admin.grouptarifForm',$data);
  }
  public function grouptarifUpdate(Request $request,$id) {
      $data["model"] = \App\Models\GroupTarif::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("grouptarifList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('grouptarifList'));
      }
      $data["modelclass"] = "\App\Models\GroupTarif";
      $data["title"] = "GroupTarif Edit";      
      return view('admin.grouptarifForm',$data);
  }
  public function grouptarifDelete() {
    $model=\App\Models\GroupTarif::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('grouptarifList'));
  }
  public function rewardhistoryList() {
      $data["modelclass"] = "\App\Models\RewardHistory";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.rewardhistoryList',$data);
  }
  public function rewardhistoryCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\RewardHistory::create($_POST);
          return redirect(route('rewardhistoryList'));
      }
      $data["model"] = new \App\Models\RewardHistory();
      $data["modelclass"] = "\App\Models\RewardHistory";
      $data["title"] = "RewardHistory Create";
      $data["group_ids"] = \App\Models\Group::all();
      $data["game_ids"] = \App\Models\Game::all();
      return view('admin.rewardhistoryForm',$data);
  }
  public function rewardhistoryUpdate(Request $request,$id) {
      $data["model"] = \App\Models\RewardHistory::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("rewardhistoryList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('rewardhistoryList'));
      }
      $data["modelclass"] = "\App\Models\RewardHistory";
      $data["title"] = "RewardHistory Edit";
      $data["group_ids"] = \App\Models\Group::all();
      $data["game_ids"] = \App\Models\Game::all();
      return view('admin.rewardhistoryForm',$data);
  }
  public function rewardhistoryDelete() {
    $model=\App\Models\RewardHistory::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('rewardhistoryList'));
  }
  public function withdrawalList() {
      $data["modelclass"] = "\App\Models\Withdrawal";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.withdrawalList',$data);
  }
  public function withdrawalCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\Withdrawal::create($_POST);
          return redirect(route('withdrawalList'));
      }
      $data["model"] = new \App\Models\Withdrawal();
      $data["modelclass"] = "\App\Models\Withdrawal";
      $data["title"] = "Withdrawal Create";
      $data["user_ids"] = \App\Models\User::all();
      return view('admin.withdrawalForm',$data);
  }
  public function withdrawalUpdate(Request $request,$id) {
      $data["model"] = \App\Models\Withdrawal::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("withdrawalList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('withdrawalList'));
      }
      $data["modelclass"] = "\App\Models\Withdrawal";
      $data["title"] = "Withdrawal Edit";
      $data["user_ids"] = \App\Models\User::all();
      return view('admin.withdrawalForm',$data);
  }
  public function withdrawalDelete() {
    $model=\App\Models\Withdrawal::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('withdrawalList'));
  }
  public function newsletterList() {
      $data["modelclass"] = "\App\Models\Newsletter";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.newsletterList',$data);
  }
  public function newsletterCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\Newsletter::create($_POST);
          return redirect(route('newsletterList'));
      }
      $data["model"] = new \App\Models\Newsletter();
      $data["modelclass"] = "\App\Models\Newsletter";
      $data["title"] = "Newsletter Create";
      $data["type_ids"] = \App\Models\NewsletterType::all();
      return view('admin.newsletterForm',$data);
  }
  public function newsletterUpdate(Request $request,$id) {
      $data["model"] = \App\Models\Newsletter::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("newsletterList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('newsletterList'));
      }
      $data["modelclass"] = "\App\Models\Newsletter";
      $data["title"] = "Newsletter Edit";
      $data["type_ids"] = \App\Models\NewsletterType::all();
      return view('admin.newsletterForm',$data);
  }
  public function newsletterDelete() {
    $model=\App\Models\Newsletter::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('newsletterList'));
  }
  public function newslettertypeList() {
      $data["modelclass"] = "\App\Models\NewsletterType";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.newslettertypeList',$data);
  }
  public function newslettertypeCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\NewsletterType::create($_POST);
          return redirect(route('newslettertypeList'));
      }
      $data["model"] = new \App\Models\NewsletterType();
      $data["modelclass"] = "\App\Models\NewsletterType";
      $data["title"] = "NewsletterType Create";
      return view('admin.newslettertypeForm',$data);
  }
  public function newslettertypeUpdate(Request $request,$id) {
      $data["model"] = \App\Models\NewsletterType::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("newslettertypeList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('newslettertypeList'));
      }
      $data["modelclass"] = "\App\Models\NewsletterType";
      $data["title"] = "NewsletterType Edit";
      return view('admin.newslettertypeForm',$data);
  }
  public function newslettertypeDelete() {
    $model=\App\Models\NewsletterType::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('newslettertypeList'));
  }
  public function roulettesprizeList() {
      $data["modelclass"] = "\App\Models\RoulettesPrize";
      $search = $_GET["qu"] ?? null;
      if($search) {
          $data["models"] = ($data["modelclass"])::where("name","like","%{$search}%")->paginate(15);
          $data["search"] = $search;
      }
      else $data["models"] = ($data["modelclass"])::paginate(15);
      return view('admin.roulettesprizeList',$data);
  }
  public function roulettesprizeCreate(Request $request) {
      if($_POST) {
          $model = \App\Models\RoulettesPrize::create($_POST);
          return redirect(route('roulettesprizeList'));
      }
      $data["model"] = new \App\Models\RoulettesPrize();
      $data["modelclass"] = "\App\Models\RoulettesPrize";
      $data["title"] = "RoulettesPrize Create";
      return view('admin.roulettesprizeForm',$data);
  }
  public function roulettesprizeUpdate(Request $request,$id) {
      $data["model"] = \App\Models\RoulettesPrize::where("id",$id)->first();
      if(!$data["model"]) return redirect(route("roulettesprizeList"));
      if($_POST) {
          $data["model"]->fill($_POST);
          $data["model"]->save();
          return redirect(route('roulettesprizeList'));
      }
      $data["modelclass"] = "\App\Models\RoulettesPrize";
      $data["title"] = "RoulettesPrize Edit";
      return view('admin.roulettesprizeForm',$data);
  }
  public function roulettesprizeDelete() {
    $model=\App\Models\RoulettesPrize::where("id",$_POST["id"])->first();
    $model->delete();
    return redirect(route('roulettesprizeList'));
  }

}