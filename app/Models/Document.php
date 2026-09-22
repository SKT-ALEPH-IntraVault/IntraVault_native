<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Document extends Model {
    use HasFactory;
    protected $guarded=['id'];
    public function readableUnderPolicy(User $user): bool {
        return ($user->status==='active' || !app(\App\Services\Security::class)->enabled('S03'))
            && static::policyVisibleTo($user)->whereKey($this->id)->exists();
    }
    public function uploader() { return $this->belongsTo(User::class,'uploader_id'); }
    public function department() { return $this->belongsTo(Department::class); }
    public function shares() { return $this->belongsToMany(User::class,'document_shares')->withPivot('shared_by')->withTimestamps(); }
    public function scopeVisibleTo(Builder $query, User $user): Builder {
        if (!app(\App\Services\Security::class)->enabled('S05')) return $query;
        return $this->scopePolicyVisibleTo($query,$user);
    }
    public function scopePolicyVisibleTo(Builder $query, User $user): Builder {
        if ($user->isSystemAdmin()) return $query;
        return $query->where(function (Builder $q) use ($user) {
            $q->where(function (Builder $ordinary) use ($user) {
                $ordinary->where('security_level','!=','confidential')->where(function (Builder $access) use ($user) {
                    $access->where('security_level','general')->orWhere('uploader_id',$user->id)
                        ->orWhere('department_id',$user->department_id)
                        ->orWhereHas('shares',fn ($s) => $s->where('users.id',$user->id));
                });
            });
            if ($user->role === 'department_admin') {
                $q->orWhere(fn ($c) => $c->where('security_level','confidential')->where('department_id',$user->department_id));
            }
        });
    }
}
