<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Uuids;
// use Ramsey\Uuid\Uuid;



class Coupon extends Model
{
    use HasFactory, SoftDeletes, Uuids;
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'code',
        'expiredDate',
        'userId',
    ];
}
