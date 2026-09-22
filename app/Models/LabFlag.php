<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LabFlag extends Model {
    public $incrementing=false;
    protected $keyType='string';
    protected $fillable=['id','enabled'];
    protected function casts(): array { return ['enabled'=>'boolean']; }
}
