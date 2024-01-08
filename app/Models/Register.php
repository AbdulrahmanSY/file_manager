<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Register extends Model
{
    use HasFactory,SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $fillable = ['user_id', 'file_id', 'operation','repo_id'];

    public function user(){
        return $this->belongsTo(User::class);
    }
    public function repo(){
        return $this->belongsTo(User::class);
    }
    public function file(){
        return $this->belongsTo(File::class);
    }
}
