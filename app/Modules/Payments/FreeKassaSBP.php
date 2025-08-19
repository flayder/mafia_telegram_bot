<?php
namespace App\Modules\Payments;

class FreeKassaSBP extends FreeKassaApi {
    public function fk_method_id()
    {
        return 44;  //карта
    }
}