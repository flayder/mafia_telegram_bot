<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MafiaEvent extends Model
{
    use HasFactory;

    protected $fillable = ['hash'];


    public static function ifDoubleClick(Array $data): bool
    {
        $hash = md5(json_encode($data));

        $newDateTime = Carbon::now()->subSeconds(10);

        //Log::info('Date sub 10', ['date' => $newDateTime->format('Y-m-d H:i:s')]);

        if(self::where('hash', $hash)->where('created_at', '>', $newDateTime->format('Y-m-d H:i:s'))->first())
            return true;

        //Log::info('Date created_at', ['date' => Carbon::now()->format('Y-m-d H:i:s')]);

        self::create([
            'hash'          => $hash,
            'created_at'    => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at'    => Carbon::now()->format('Y-m-d H:i:s')
        ]);

        return false;
    }

}
