<?php
namespace App\Services;
use App\Models\LabFlag;
class Lab {
    public function enabled(string $id): bool {
        if (!config('lab.enabled') || !array_key_exists($id,config('lab.vulnerabilities'))) return false;
        // Reuse within this request only; the next request always reads current settings.
        return RequestSettings::enabled('lab_flags',$id,false);
    }
}
