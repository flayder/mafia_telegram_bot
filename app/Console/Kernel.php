<?php

namespace App\Console;

use App\Models\BotGroup;
use App\Models\BotUser;
use App\Models\Game;
use App\Models\GroupTarif;
use App\Models\Newsletter;
use App\Models\NewsletterSended;
use App\Models\Task as TaskModel;
use App\Modules\Bot\AppBot;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected $games = [];
    protected function getGame($game_id) {
        if(!isset($games[$game_id])) {
            $games[$game_id] = Game::where('id',$game_id)->first();
        }
        return $games[$game_id];
    }
    protected function schedule(Schedule $schedule)
    {           
        $bot = AppBot::appBot();        
        $tasks = TaskModel::where('is_active',1)->get();
        foreach($tasks as $task) {
            if($task->delay > 0) { //прошло ли время
                $createMoment = strtotime($task->created_at);
                $execMoment = $createMoment + $task->delay;
                $time = time();
                if($execMoment > $time) {
                    if($execMoment - $time > 5) continue; //этот цикл выполним без нее
                    else sleep($execMoment - $time); //ждем и выполняем
                } 
            }
            $task->is_active = 0; //отметка о выполнении
            $task->save();
            $game = $this->getGame($task->game_id);
            if($game->status == 2) continue;
            
            $options = json_decode($task->options,true);
            $class = $options['class'];
            $method = $options['method'];
            $param = $options['param'] ?? null;
            if($param) $class::$method($param);
            else $class::$method();

            //Log::info('Task: {id}', ['id' => $task->id]);
            //Log::info('Game: {arr}', ['arr' => print_r($game, true)]);
            //Log::info('Options: {arr}', ['arr' => print_r($options, true)]);
            //Log::info('Class: {arr}', ['arr' => print_r($class, true)]);
            //Log::info('Method: {arr}', ['arr' => print_r($method, true)]);
            //Log::info('Param: {arr}', ['arr' => print_r($param, true)]);
        }
        //задачи по расписанию
        $schedule->call(function () { //отключаем закончившиеся тарифы
            $date = date('Y-m-d H:i:s');
            $otklGrps = BotGroup::where('tarif_id','>',1)->where('tarif_expired','<',$date)->get();
            foreach($otklGrps as $grp) {
                GroupTarif::clearTarif($grp);
            }
         })->dailyAt('03:05'); 
         //рассылки
         $newsletter = Newsletter::whereIn('status',[1,2])->orderBy('id')->first(); //запускаем только одну. Следующую на сделующий запуск крона
         if($newsletter) {
            if($newsletter->status == 1) { //чистая, никому не отправили
                $newsletter->status = 2;
                $newsletter->save();                
                $users = BotUser::limit(5)->get();
            }
            else {
                $sended = NewsletterSended::where('newsletter_id',$newsletter->id)->get()->all();
                $sendedUserIds = array_column($sended, 'user_id');
                $users = BotUser::whereNotIn('id',$sendedUserIds)->limit(5)->get();
            }
            if($users->count()) {
                $message = json_decode($newsletter->message,true);
                foreach($users as $user) {
                    $message['chat_id'] = $user->id;
                    try {
                        $bot->getApi()->copyMessage($message);
                        NewsletterSended::create(['user_id'=>$user->id,'newsletter_id'=>$newsletter->id,'status'=>1]);
                    }
                    catch(Exception $e) {
                        NewsletterSended::create(['user_id'=>$user->id,'newsletter_id'=>$newsletter->id,'status'=>2,'error'=>$e->getMessage()]);
                    }
                }
            }
            else {
                $newsletter->status = 3;
                $newsletter->save();  
                $mess['text'] = "<b>Рассылка завершена!</b>";
                $message = json_decode($newsletter->message,true);
                $bot->sendAnswer([$mess],$message['from_chat_id']);
            }
         }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
