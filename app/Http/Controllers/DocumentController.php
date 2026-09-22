<?php
namespace App\Http\Controllers;
use App\Models\{Document,Department,User};
use App\Services\{Audit,Lab,DocumentFiles};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Gate,Storage};
use Illuminate\Validation\Rule;
class DocumentController extends Controller {
    public function index(Request $r) {
        $r->validate(['q'=>'nullable|string|max:200','department'=>'nullable|integer','level'=>['nullable',Rule::in(['general','department','confidential'])]]);
        $query=Document::with(['department','uploader'])->visibleTo($r->user());
        if ($r->filled('level')) $query->where('security_level',$r->input('level'));
        if ($r->routeIs('favorites')) $query->whereIn('id',$r->user()->favorites()->select('documents.id'));
        // Counts reflect the current workspace and permissions, before search/department filtering.
        $departmentCounts=(clone $query)->selectRaw('department_id, COUNT(*) AS total')->groupBy('department_id')->pluck('total','department_id');
        if ($r->filled('q')) {
            $term='%'.$r->string('q').'%';
            if (app(\App\Services\Security::class)->enabled('S10')) {
                $query->where(fn ($q) => $q->where('title','like',$term)->orWhere('description','like',$term)->orWhere('original_filename','like',$term));
            } else {
                // Deliberately unbound app-search path for the S10 exercise.
                $query->whereRaw("(title LIKE '".$term."' OR description LIKE '".$term."' OR original_filename LIKE '".$term."')");
            }
        }
        if ($r->filled('department')) $query->where('department_id',$r->integer('department'));
        $documents=$query->latest()->paginate(20)->withQueryString();
        if ($documents->currentPage() > $documents->lastPage()) {
            return redirect()->to($r->fullUrlWithQuery(['page'=>$documents->lastPage()]));
        }
        return view('documents.index',['documents'=>$documents,
            'departments'=>Department::orderBy('name')->get(),'departmentCounts'=>$departmentCounts]);
    }
    public function create(Request $r) { return $this->form($r,new Document); }
    public function edit(Request $r, Document $document) {
        Gate::authorize('update',$document);
        return $this->form($r,$document);
    }
    private function form(Request $r, Document $document) {
        $departments=($r->user()->isSystemAdmin() || !app(\App\Services\Security::class)->enabled('S07')) ? Department::orderBy('name')->get() : Department::whereKey($r->user()->department_id)->get();
        if ($document->exists && !$departments->contains('id',$document->department_id)) $departments->push($document->department);
        return view('documents.form',compact('document','departments'));
    }
    private function metadata(Request $r, ?Document $document=null): array {
        $data=$r->validate(['title'=>'required|string|max:255','description'=>'nullable|string|max:20000',
            'department_id'=>'required|exists:departments,id','security_level'=>['required',Rule::in(['general','department','confidential'])]]);
        if (app(\App\Services\Security::class)->enabled('S07') && !$r->user()->isSystemAdmin()) {
            $allowed=[$r->user()->department_id];
            if ($document) $allowed[]=$document->department_id;
            abort_unless(in_array((int)$data['department_id'],$allowed,true),403);
            if ($r->user()->role === 'employee') abort_if($data['security_level']==='confidential',403);
            if ($data['security_level']==='confidential') abort_unless((int)$data['department_id']===$r->user()->department_id,403);
        }
        return $data;
    }
    public function store(Request $r, DocumentFiles $files) {
        $data=$this->metadata($r);
        $r->validate(['file'=>'required|file']);
        $file=$files->store($r->file('file'));
        try {
            $document=DB::transaction(function () use ($data,$file,$r) {
                $doc=Document::create($data+$file+['uploader_id'=>$r->user()->id]);
                Audit::record('document.upload','document',$doc->id); return $doc;
            });
        } catch (\Throwable $e) { Storage::disk('local')->delete($file['storage_path']); throw $e; }
        return redirect()->route('documents.show',$document)->with('status','문서를 등록했습니다.');
    }
    public function update(Request $r, Document $document, DocumentFiles $files) {
        Gate::authorize('update',$document); $data=$this->metadata($r,$document);
        $replacement=$r->hasFile('file') ? $files->store($r->file('file')) : [];
        $old=$document->storage_path;
        try {
            DB::transaction(function () use ($document,$data,$replacement) {
                $document->update($data+$replacement);
                if (app(\App\Services\Security::class)->enabled('S08') && $document->security_level==='confidential') $document->shares()->detach();
                Audit::record('document.update','document',$document->id);
            });
        } catch (\Throwable $e) { if ($replacement) Storage::disk('local')->delete($replacement['storage_path']); throw $e; }
        if ($replacement) Storage::disk('local')->delete($old);
        return redirect()->route('documents.show',$document)->with('status','문서를 수정했습니다.');
    }
    public function show(Request $r, Document $document, Lab $lab) {
        if (!$lab->enabled('V01')) Gate::authorize('view',$document);
        Audit::record('document.view','document',$document->id,context:['V01'=>$lab->enabled('V01')]);
        $canShare=Gate::allows('share',$document);
        $recipients=$canShare ? User::where('status','active')->orderBy('name')->get() : collect();
        return view('documents.show',compact('document','recipients','canShare')+['rawDescription'=>!app(\App\Services\Security::class)->enabled('S11') || $lab->enabled('V07')]);
    }
    public function download(Request $r, Document $document, Lab $lab, DocumentFiles $files) {
        if (app(\App\Services\Security::class)->enabled('S06') && !$lab->enabled('V02')) {
            abort_unless($document->readableUnderPolicy($r->user()),403);
        }
        $r->validate(['path'=>'nullable|string|max:500']);
        if ($r->filled('path')) {
            abort_if(app(\App\Services\Security::class)->enabled('S13'),403);
            $path=$files->resolveAlternative($r->input('path'));
        } else $path=$files->resolve($document->storage_path);
        Audit::record('document.download','document',$document->id,context:['V02'=>$lab->enabled('V02')]);
        return response()->download($path,$document->original_filename,['Content-Type'=>'application/octet-stream','X-Content-Type-Options'=>'nosniff','Cache-Control'=>'private, no-store']);
    }
    public function destroy(Document $document) {
        Gate::authorize('delete',$document); $path=$document->storage_path;
        DB::transaction(function () use ($document) { Audit::record('document.delete','document',$document->id); $document->delete(); });
        Storage::disk('local')->delete($path);
        return redirect()->route('documents.index')->with('status','문서를 삭제했습니다.');
    }
    public function favorite(Request $r, Document $document) {
        Gate::authorize('view',$document);
        $r->user()->favorites()->toggle($document->id);
        Audit::record('document.favorite','document',$document->id);
        return back()->with('status','즐겨찾기를 변경했습니다.');
    }
    public function share(Request $r, Document $document) {
        Gate::authorize('share',$document);
        $data=$r->validate(['user_id'=>'required|exists:users,id']);
        $document->shares()->syncWithoutDetaching([$data['user_id']=>['shared_by'=>$r->user()->id]]);
        Audit::record('document.share','document',$document->id,context:['recipient_id'=>(int)$data['user_id']]);
        return back()->with('status','직접 공유를 추가했습니다.');
    }
    public function unshare(Document $document, User $user) {
        Gate::authorize('share',$document); $document->shares()->detach($user->id);
        Audit::record('document.unshare','document',$document->id,context:['recipient_id'=>$user->id]);
        return back()->with('status','공유를 해제했습니다.');
    }
}
