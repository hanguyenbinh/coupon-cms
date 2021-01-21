<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Uuids;


class CouponRedeem extends Model
{
    use HasFactory, SoftDeletes, Uuids;
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'couponId',
        'redeemBy'        
    ];
}
