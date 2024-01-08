<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use HasFactory , SoftDeletes;
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'name',
        'status',
        'path',
        'repo_id',
    ];
    public function repo(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Repo::class,'repo_id','id');
    }
}
