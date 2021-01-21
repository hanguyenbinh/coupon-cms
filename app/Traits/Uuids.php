<?php
namespace App\Traits;
use Ramsey\Uuid\Uuid;
trait Uuids
{
    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        
        static::creating(function ($model) {
            // if (empty($model->{$model->getKeyName()})) {
            //     $model->{$model->getKeyName()} = Uuid::uuid4()->toString();
            // }
            // $model->uuid = Uuid::uuid4()->toString();
            $model->{$model->getKeyName()} = Uuid::uuid4()->toString();
        });
        static::saving(function ($model) {
            // What's that, trying to change the UUID huh?  Nope, not gonna happen.
            $original_uuid = $model->getOriginal('uuid');
    
            if ($original_uuid !== $model->uuid) {
                $model->uuid = $original_uuid;
            }
        });
        parent::boot();
    }
    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
    }
    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType()
    {
        return 'string';
    }
}