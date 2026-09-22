<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
    public function up(): void {
        Schema::create('security_controls',function (Blueprint $t) {
            $t->string('id',3)->primary(); $t->boolean('enabled')->default(true); $t->timestamps();
        });
        Schema::table('users',fn (Blueprint $t)=>$t->boolean('is_lab_guest')->default(false));
        // New controls start protected. Preserve old V flags as scoped legacy settings
        // until an explicit preset is chosen; do not silently broaden an active flaw.
        foreach (array_keys(config('security.controls')) as $id) {
            DB::table('security_controls')->insert(['id'=>$id,'enabled'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }
    }
    public function down(): void {
        Schema::table('users',fn (Blueprint $t)=>$t->dropColumn('is_lab_guest'));
        Schema::dropIfExists('security_controls');
    }
};
