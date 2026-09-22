<?php
namespace App\Policies;
use App\Models\{Document,User};
class DocumentPolicy {
    public function view(User $user, Document $document): bool {
        return ($user->status === 'active' || !app(\App\Services\Security::class)->enabled('S03')) && Document::visibleTo($user)->whereKey($document->id)->exists();
    }
    public function update(User $user, Document $document): bool {
        if (!app(\App\Services\Security::class)->enabled('S07')) return true;
        if (!$document->readableUnderPolicy($user)) return false;
        return $user->isSystemAdmin() || $document->uploader_id === $user->id
            || ($user->role === 'department_admin' && $document->department_id === $user->department_id);
    }
    public function delete(User $user, Document $document): bool { return $this->update($user,$document); }
    public function share(User $user, Document $document): bool {
        if (!app(\App\Services\Security::class)->enabled('S08')) return true;
        return $document->security_level !== 'confidential' && $document->readableUnderPolicy($user)
            && ($user->isSystemAdmin() || $document->uploader_id===$user->id || ($user->role==='department_admin' && $document->department_id===$user->department_id));
    }
}
