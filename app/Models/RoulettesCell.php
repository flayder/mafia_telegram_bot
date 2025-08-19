<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoulettesCell extends Model
{
    use HasFactory;
    protected $table = 'roulettes_cells';
    protected $fillable = ['roulette_id', 'cell_number','prize_id','is_open'];
    public function prize() {
        return $this->hasOne(RoulettesPrize::class, 'id', 'prize_id');
    }
    public function getCaptionAttribute() {
        if($this->is_open) {
            if($this->prize_id) return $this->prize;
            else return '🟧';
        }
        return '🧳';
    }
}
