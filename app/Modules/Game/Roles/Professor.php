<?php
namespace App\Modules\Game\Roles;

use App\Models\DeactivatedCommand;
use App\Models\GameUser;
use App\Models\GamerParam;
use App\Models\Union;
use App\Models\UnionParticipant;
use App\Modules\Game\Game;
use App\Modules\Bot\AppBot;

trait Professor {
    public static function professor_select($params) {
        self::gamer_set_move($params, 'professor_select', 'professor_select_itog'); 
    }
    public static function professor_teach($params) {
        $params['cmd_param'] = 1;
        self::gamer_set_move($params, 'professor_teach', 'professor_teach_itog'); 
    }
    public static function professor_select_itog($game) {
        $gameParams = GamerParam::gameParams($game);
        if(!isset($gameParams['professor_select'])) return null; //ошибочный запуск функции
        $victim = GameUser::where('id',$gameParams['professor_select'])->first(); //потерпевший
        if(!$victim) return null; //ошибочный запуск функции
        $professor = GameUser::where('game_id',$game->id)->where('role_id',22)->first();
        if(!$professor || !self::isCanMove($professor)) {  //обездвижен       
            GamerParam::deleteAction($game, 'professor_select'); 
            return;
        } 
        $profMess = ['text'=>"Ваш выбор неверный, ".Game::userUrlName($victim->user)." — НЕ 👩‍🎓Студент!"];
        $victimMess = ['text'=>"👨🏻‍🏫Профессор проверил, его ли вы ученик..."];
        if($victim->role_id == 21) { //нашел студента
            $profMess = ['text'=>"Ваш выбор верный, ".Game::userUrlName($victim->user)." — 👩‍🎓Студент! Обучайте его и выиграете."];
            $victimMess = ['text'=>"👨🏻‍🏫Профессор навестил тебя и убедился, что ты его ученик!"];
            GamerParam::saveParam($professor, "professor_know_stud",$victim->id);
            DeactivatedCommand::create(['game_id'=>$game->id,'command'=>'professor_select']);
        }
        $bot = AppBot::appBot();
        $bot->sendAnswer([$profMess],$professor->user_id);

        self::ciganka_message($victim, $victimMess['text']);
        $bot->sendAnswer([$victimMess],$victim->user_id);
    }
    public static function professor_teach_itog($game) {
        $gameParams = GamerParam::gameParams($game);
        if(!isset($gameParams['professor_teach'])) return null; //ошибочный запуск функции        
        $professor = GameUser::where('game_id',$game->id)->where('role_id',22)->first();
        //professor_know_stud
        $studParam = GamerParam::where(['game_id'=>$game->id,'param_name'=>'professor_know_stud'])->first();
        if(!$studParam) return; //профессор не нашел студента - ошибочный запуск

        $student = GameUser::where('id',$studParam->param_value)->first();
        if(!$professor || !self::isCanMove($professor)) {  //обездвижен       
            GamerParam::deleteAction($game, 'professor_teach'); 
            return;
        } 
        if(!$student) return;
        $bot = AppBot::appBot();
        if(!$student->isActive()) {
            $profMess = ['text'=>"👩‍🎓Студент умер, так и не дойдя до конца обучения"];
            $bot->sendAnswer([$profMess],$professor->user_id);
            return;
        }
        $profMess = ['text'=>"Ваш 👩‍🎓студент проходит обучение"];
        $groupMess = ['text'=>"👩‍🎓Студент получает профессию..."];                
        $paramCollection = GamerParam::where(['game_id'=>$game->id,'param_name'=>'professor_teach'])->get();
        if($paramCollection->count() > 1) {
            $studMess = ['text'=>"Твое обучение завершено, 👨🏻‍🏫профессор обучил тебя всему что знал сам! Удачной работы с мафией.."];
            $groupMess = ['text'=>"👩‍🎓Студент получил профессию..."];
            $profMess = ['text'=>"Вы успешно обучили 👩‍🎓студента"];
            $bot->sendAnswer([$studMess],$student->user_id);
            DeactivatedCommand::create(['game_id'=>$game->id,'command'=>'professorteach']);
            //делаем из студента того, кем он хочет быть
            $studParam = GamerParam::where(['game_id'=>$game->id,'param_name'=>'student_fak'])->first();
            if(!$studParam) return; //остался AFK если ничего не выбрал
            if($studParam->param_value != 'mafdoc') { //мафдок не должен попадать в союз
                $mafunParam = GamerParam::where(['game_id'=>$game->id,'param_name'=>'maf_union'])->first();
                UnionParticipant::create(['union_id'=>$mafunParam->param_value,'gamer_id'=>$student->id,'game_id'=>$game->id]);
            }
            // профессор стал афк после обучения
            GamerParam::saveParam($professor, 'afk', $professor->id);
            $rolesOfCase = ['advokat'=>19,'mafdoc'=>16, 'mafi'=>25];
            $student->role_id = $rolesOfCase[$studParam->param_value];
            $student->save();

            $addText = "\nТы играешь на стороне мафии. Каждую ночь ты посещаешь одного из игроков. Если на твой выбор будет совершено нападение, и этот игрок является отрицательной ролью, то сможешь вылечить его.";

            if($rolesOfCase[$studParam->param_value] == 16)
                $studMess['text'] .= "\n Твоя роль — 👨🏻‍⚕ Подпольный врач!".$addText;
            elseif($rolesOfCase[$studParam->param_value] == 19)
                $studMess['text'] .= "\n Твоя роль — 👨🏼‍💼 Адвокат!";
            elseif($rolesOfCase[$studParam->param_value] == 25)
                $studMess['text'] .= "\n Твоя роль — 🤵🏻 Мафия!";

            GamerParam::saveParam($student,'nightactionempty',1); //"лечим" от АФК
            //сообщение для цыганки
            self::ciganka_message($student, $studMess['text']);
        }
        $bot->sendAnswer([$profMess],$professor->user_id);
        $bot->sendAnswer([$groupMess],$game->group_id);
    }
}