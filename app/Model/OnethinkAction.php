<?php

declare (strict_types=1);
namespace App\Model;

/**
 * @property int $id 
 * @property string $name 
 * @property string $title 
 * @property string $remark 
 * @property string $rule 
 * @property string $log 
 * @property int $type 
 * @property int $status 
 * @property int $update_time 
 */
class OnethinkAction extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'onethink_action';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'type' => 'integer', 'status' => 'integer', 'update_time' => 'integer'];
}