<?php

namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;

trait Muzh
{
    public static function muzh_select($params)
    {
        self::gamer_set_move($params, 'muzh_select', 'muzh_select_itog');
    }
    public static function muzh_select_itog($game)
    {
        $muzh = GameUser::where('game_id', $game->id)->where('role_id', 34)->first();
        if (!$muzh || !self::isCanMove($muzh)) return null;
        $gameParams = GamerParam::gameParams($game);
        if (!isset($gameParams['muzh_select'])) return null;
        $victim = GameUser::where('id', $gameParams['muzh_select'])->first();
        if (!$victim) return;
        $doctorText = "👨🏼‍⚕Доктор спас вас этой ночью от 💍Мужа";
        $vedmaText = "🧝‍♀️ Ведьма сварила для тебя лечебное зелье! Она исцелила тебя от 💍Мужа";
        if ($victim->role_id == 6) {

            if (!self::isTreated($victim->id, $game, 6)) {
                $message = ['text' => "Ваш выбор верный, " . Game::userUrlName($victim->user) . " — 💃Красотка! Теперь не кому очаровывать наш город.. ведь 💃Красотка погибает."];
                Game::message(['message' => $message, 'chat_id' => $muzh->user_id]);
                GamerParam::saveParam($muzh, 'muzh_win', 1, false);
                GamerParam::saveParam($muzh, 'afk', $muzh->id);
                GamerParam::saveParam($muzh, 'mir', $muzh->id);
                self::user_deactivate(['killer_id' => $muzh->id, 'cmd_param' => $gameParams['muzh_select']], false);

                //сообщение для цыганки
                self::ciganka_message($victim, $message['text']);
            } else {
                if (self::isDoctorTreate($game, $victim->id)) {
                    
                    self::setDoctorTreatMessage(
                        $victim->id,
                        $victim->user_id,
                        $doctorText,
                        "👨🏼‍⚕Доктор спас " . Game::userUrlName($victim->user) . "  от 💍Мужа",
                        "Вы спасли " . Game::userUrlName($victim->user) . " от 💍Мужа",
                        1
                    );

                    //сообщение для цыганки
                    self::ciganka_message($victim, $mushText);
                }
                if (self::isVedmaTreat($game, $victim->id)) {
                    
                    self::setVedmaTreatMessage(
                        $victim->id,
                        $victim->user_id,
                        $vedmaText,
                        "Вы спасли " . Game::userUrlName($victim->user) . " от 💍Мужа",
                        1
                    );

                    //сообщение для цыганки
                    self::ciganka_message($victim, $vedmaText);
                }
            }
        }
        if (isset($gameParams['krasotka_select']) && $gameParams['muzh_select'] == $gameParams['krasotka_select']) {
            //красотка и ее выбор погибают, а муж становится мирной ролью
            $message = ['text' => "Вы попали на выбор 💃Красотки! Теперь не кому очаровывать наш город.. ведь 💃Красотка и её выбор погибают."];
            Game::message(['message' => $message, 'chat_id' => $muzh->user_id]);
            if (!self::isTreated($victim->id, $game)) {
                self::user_deactivate(['killer_id' => $muzh->id, 'cmd_param' => $gameParams['muzh_select']], false);
                $krasotka = self::getKrasotka($game->id);
                if($krasotka) {
                    //сообщение для цыганки
                    self::ciganka_message($krasotka, $vedmaText);
                }
                
            } else { //выясним кто спас и добавим сообщения
                if (self::isDoctorTreate($game, $victim->id)) {
                    self::setDoctorTreatMessage(
                        $victim->id,
                        $victim->user_id,
                        $doctorText,
                        "👨🏼‍⚕Доктор спас " . Game::userUrlName($victim->user) . "  от 💍Мужа",
                        "Вы спасли " . Game::userUrlName($victim->user) . " от 💍Мужа",
                        1
                    );

                    //сообщение для цыганки
                    self::ciganka_message($victim, $doctorText);
                }
                if (self::isVedmaTreat($game, $victim->id)) {
                    self::setVedmaTreatMessage(
                        $victim->id,
                        $victim->user_id,
                        $vedmaText,
                        "Вы спасли " . Game::userUrlName($victim->user) . " от 💍Мужа",
                        1
                    );

                    //сообщение для цыганки
                    self::ciganka_message($victim, $vedmaText);
                }
            }
            $krasotka = self::getKrasotka($game->id);
            if (!self::isTreated($krasotka->id, $game, 6)) {
                self::user_deactivate(['killer_id' => $muzh->id, 'cmd_param' => $krasotka->id], false);
                GamerParam::saveParam($muzh, 'muzh_win', 1, false);
                GamerParam::saveParam($muzh, 'afk', $muzh->id);
                GamerParam::saveParam($muzh, 'mir', $muzh->id);
            } else { //выясним кто спас и добавим сообщения
                if (self::isDoctorTreate($game, $krasotka->id)) {
                    self::setDoctorTreatMessage(
                        $krasotka->id,
                        $krasotka->user_id,
                        $doctorText,
                        "👨🏼‍⚕Доктор спас " . Game::userUrlName($krasotka->user) . "  от 💍Мужа",
                        "Вы спасли " . Game::userUrlName($krasotka->user) . " от 💍Мужа",
                        1
                    );

                    //сообщение для цыганки
                    self::ciganka_message($krasotka, $doctorText);
                }
                if (self::isVedmaTreat($game, $krasotka->id)) {
                    self::setVedmaTreatMessage(
                        $krasotka->id,
                        $krasotka->user_id,
                        $vedmaText,
                        "Вы спасли " . Game::userUrlName($krasotka->user) . " от 💍Мужа",
                        1
                    );

                    //сообщение для цыганки
                    self::ciganka_message($krasotka, $vedmaText);
                }
            }
        }
    }
}
