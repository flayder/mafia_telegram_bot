<?php

namespace App\Models;

use App\Modules\Functions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Roulette extends Model
{
    use HasFactory;
    protected $table = 'roulettes';
    protected $fillable = ['user_id','status'];

    public static function generateRoulette($user_id) {
        $roulette = self::create(['user_id'=>$user_id]);
        $roulette->generateCells();
        return $roulette;
    }
    public function generateCells() {
        //RoulettesCell               
        $season = Functions::getSeason();
        $dbPrizes = RoulettesPrize::whereIn('season',[0, $season])->get();
        $prizesOfType = [];
        foreach($dbPrizes as $dbPrize) {
            $prizesOfType[$dbPrize->prize_type][] = $dbPrize;
        }
        //строим отрезок вероятностей
        $probabilities = [];
        $pos = 0;
        $jekpots = [];
        foreach($prizesOfType[0] as $prize0type) {
            $x = $prize0type->percent * 10;
            $pos += $x;
            $probabilities[] = ['prize_id'=>$prize0type->id, 'val'=>$x, 'lastpos'=>$pos];
            if($prize0type->percent == 1) $jekpots[] = $prize0type->id;
        }
        foreach($prizesOfType as $k=>$prtp) {
            if($k == 0) continue;
            $probabilities[] = ['prize_type'=>$k, 'val'=>$prtp[0]->percent * 10];
        }
                
        $prizes = [];
        $prizeTypes = []; 
        for($i=0;$i<30;$i++) {
            $propVals = array_column($probabilities,'val');
            $valsSum = array_sum($propVals);
            $pos = 0;
            for($j=0;$j<count($probabilities);$j++) {
                $pos += $probabilities[$j]['val'];
                $probabilities[$j]['lastpos'] = $pos;
            }

            $rc = new RoulettesCell(['roulette_id'=>$this->id, 'cell_number'=>$i]);
            $isPrize = (count($prizes) < 12) ? random_int(0,1) : 0;
            if((29-$i) <= (12 - count($prizes))) $isPrize = 1;
            if($isPrize) {
                $val = random_int(1,$valsSum);
                $pbNum = null;
                for($j=0;$j<count($probabilities);$j++) {
                    if($val <= $probabilities[$j]['lastpos']) {
                        $pbNum = $j;
                        break;
                    }
                }
                if($pbNum) {
                    $pb = $probabilities[$pbNum];
                    if(isset($pb['prize_id']))  {
                        $rc->prize_id = $pb['prize_id'];
                        $prizes[] = $rc->prize_id;
                        $prizeTypes[] = 0;
                        $prizeValsCnts = array_count_values($prizes);
                        if( $prizeValsCnts[$rc->prize_id]>2 ) {
                            unset($probabilities[$pbNum]);
                            $probabilities = array_values($probabilities);
                        }
                    }
                    else {
                        $prOfType = $prizesOfType[$pb['prize_type']];
                        $rc->prize_id = $prOfType[random_int(0,count($prOfType)-1)]->id;
                        $prizes[] = $rc->prize_id;
                        $prizeTypes[] = $pb['prize_type'];
                        $prizeValsCnts = array_count_values($prizeTypes);
                        if( $prizeValsCnts[$pb['prize_type']]>2 ) {
                            unset($probabilities[$pbNum]);
                            $probabilities = array_values($probabilities);
                        }
                    }
                    
                }
                else $rc->prize_id = 0;
            }
            else $rc->prize_id = 0;
            $rc->save();            
        }
        //есть ли джекпот в призах
        $isJekpot = 0;
        foreach($jekpots as $jek) {
            if(in_array($jek,$prizes)) {
                $isJekpot = 1;
                break;
            }
        }
        $prizeCells = RoulettesCell::where('roulette_id',$this->id)->where('prize_id','>',0)->get()->all();
        if(!$isJekpot) { //заменяем произвольный приз на джекпот
            $jnum = random_int(0, count($jekpots)-1);            
            $cellnum = random_int(0,count($prizeCells)-1);
            $prizeCells[$cellnum]->prize_id = $jekpots[$jnum];
            $prizeCells[$cellnum]->save();
        }
        if(count($prizeCells) < 12) {
            $addPrizeCnt = 12 - count($prizeCells);
            $noPrizeCells = RoulettesCell::where('roulette_id',$this->id)->where('prize_id',0)->get()->all();
            $setpr = [];
            $xprizesIds = [1,6,7,8];
            for($ai=0;$ai<$addPrizeCnt;$ai++) {
                do {
                    $prizepos = random_int(0,count($noPrizeCells)-1);
                }
                while(in_array($prizepos,$setpr));
                $setpr[] = $prizepos;
                $noPrizeCells[$prizepos]->prize_id = $xprizesIds[random_int(0, count($xprizesIds)-1)];
                $noPrizeCells[$prizepos]->save();
            }
        }

    }
    public function cells() {
        return $this->hasMany(RoulettesCell::class, 'roulette_id', 'id');
    }
}
