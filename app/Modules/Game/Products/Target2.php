<?php
namespace App\Modules\Game\Products;

class Target2 extends Target {
    public function activate(array $params = [])
    {
        $params['night_count'] = 2;
        return parent::activate($params);
    }
}