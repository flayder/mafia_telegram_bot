<?php
namespace App\Modules\Game\Products;

class Target5 extends Target {
    public function activate(array $params = [])
    {
        $params['night_count'] = 5;
        return parent::activate($params);
    }
}