<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Repo extends Model
{
    use HasFactory,
        SoftDeletes
//        ,QueryCacheable
        ;
    protected $dates = ['deleted_at'];
//    public $cacheFor = 600;
    protected $fillable = [
        'name',
    ];
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class,'repo_users','repo_id','user_id');
    }
    public function files(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(File::class,'repo_id','id');
    }

}
