<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Uuids;



class Coupon extends Model
{
    use HasFactory, SoftDeletes, Uuids;
    protected $fillable = [
        'code',
        'expiredDate',
        'userId',
    ];
    public $incrementing = false;
}
