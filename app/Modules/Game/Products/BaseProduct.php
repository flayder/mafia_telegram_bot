<?php
namespace App\Modules\Game\Products;

use App\Models\UserProduct;
use App\Modules\Game\Currency;

abstract class BaseProduct {
    protected UserProduct $product;
    public function __construct(UserProduct $product)
    {
        $this->product = $product;
    }
    protected function addReward() {
        if(isset(Currency::KURSES_WINDCOIN[$this->product->product->cur_code])) {
            $koef = Currency::KURSES_WINDCOIN[$this->product->product->cur_code];
            $grp = $this->product->group;
            $grp->addReward($this->product->product->price * $koef, "Активация <b>{$this->product->product}</b>");            
        }
    }
    abstract public function message();
    abstract public function activate(array $params = []);
    abstract public function deactivate();
}