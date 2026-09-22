<?php
namespace App\Services;
use App\Models\AuditLog;
class Audit {
    public static function record(string $event, ?string $type=null, string|int|null $id=null, string $result='success', array $context=[]): void {
        AuditLog::create(['user_id'=>auth()->id(),'event'=>$event,'target_type'=>$type,
            'target_id'=>$id === null ? null : (string)$id,'result'=>$result,'context'=>$context]);
    }
}
