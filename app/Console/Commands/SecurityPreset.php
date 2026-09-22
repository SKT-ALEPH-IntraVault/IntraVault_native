<?php
namespace App\Console\Commands;
use App\Services\Security;
use Illuminate\Console\Command;
class SecurityPreset extends Command {
    protected $signature='intravault:security {mode : on, off, or status}';
    protected $description='Inspect or set application security controls; on means protection enabled';
    public function handle(Security $security): int {
        $mode=$this->argument('mode');
        if (!in_array($mode,['on','off','status'],true)) { $this->error('Use on, off, or status.'); return self::FAILURE; }
        if ($mode!=='status') {
            if (!config('lab.enabled')) { $this->error('LAB_ENABLED is false.'); return self::FAILURE; }
            $security->apply(array_fill_keys(array_keys(config('security.controls')),$mode==='on'));
        }
        $this->table(['ID','보안 기능','상태'],collect($security->states())->map(fn ($v,$id)=>[$id,config('security.controls.'.$id)[0],$v?'ON':'OFF'])->all());
        return self::SUCCESS;
    }
}
