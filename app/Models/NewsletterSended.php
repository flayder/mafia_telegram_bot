<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterSended extends Model
{
    use HasFactory;
    protected $table = 'newsletter_sended';
    protected $fillable = ['user_id','newsletter_id','status','error'];
}
