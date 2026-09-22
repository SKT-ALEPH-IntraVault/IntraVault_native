<?php
namespace App\Services;
use App\Models\{SecurityControl,LabFlag};
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
class Security {
    public function enabled(string $id): bool {
        if (!array_key_exists($id,config('security.controls'))) throw new InvalidArgumentException('Unknown security control: '.$id);
        if (!config('lab.enabled')) return true;
        return RequestSettings::enabled('security_controls',$id,true);
    }
    public function states(): array {
        $states=[];
        foreach (array_keys(config('security.controls')) as $id) $states[$id]=$this->enabled($id);
        return $states;
    }
    public function apply(array $changes): void {
        foreach ($changes as $id=>$value) {
            if (!array_key_exists($id,config('security.controls')) || !is_bool($value)) throw new InvalidArgumentException('Invalid security setting.');
        }
        DB::transaction(function () use ($changes) {
            // Lock in a stable order so presets and individual writes are atomic.
            $rows=SecurityControl::orderBy('id')->lockForUpdate()->get();
            $before=$rows->pluck('enabled','id')->all();
            foreach ($changes as $id=>$value) SecurityControl::updateOrCreate(['id'=>$id],['enabled'=>$value]);
            LabFlag::query()->update(['enabled'=>false]);
            Audit::record('security.configure','security',null,context:['before'=>$before,'changes'=>$changes]);
        });
        RequestSettings::forget();
    }
}
