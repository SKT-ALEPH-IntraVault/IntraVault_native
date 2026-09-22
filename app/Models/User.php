<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable {
    use HasFactory, Notifiable;
    protected $fillable = ['employee_number','name','email','password','department_id','role','status'];
    protected $hidden = ['password','remember_token'];
    protected function casts(): array { return ['password'=>'hashed','is_lab_guest'=>'boolean']; }
    public function department() { return $this->belongsTo(Department::class); }
    public function favorites() { return $this->belongsToMany(Document::class,'favorites')->withTimestamps(); }
    public function isSystemAdmin(): bool { return $this->role === 'system_admin'; }
}
