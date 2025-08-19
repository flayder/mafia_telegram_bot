<?php
namespace App\Modules\Game\Roles;

trait Teni {
    public static function teni_select($params) {
        self::gamer_set_move($params,'teni_select'); //итог не нужен, так как ночной выбор засчитывается в любом случае
    }  
}