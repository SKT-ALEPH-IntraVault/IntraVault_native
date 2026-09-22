<?php
namespace App\Services;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Storage,Validator};
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Str;
class DocumentFiles {
    public const EXTENSIONS=['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','zip','jpg','jpeg','png'];
    public function validate(UploadedFile $file, bool $unsafe=false): void {
        $rules=['required','file','max:40960'];
        if (!$unsafe && app(Security::class)->enabled('S12')) { $rules[]=File::types(self::EXTENSIONS)->max(40960); $rules[]='extensions:'.implode(',',self::EXTENSIONS); }
        Validator::make(['file'=>$file],['file'=>$rules])->validate();
    }
    public function store(UploadedFile $file, string $directory='documents', bool $unsafe=false): array {
        $this->validate($file,$unsafe);
        $name=Str::uuid().'.bin';
        $path=$file->storeAs($directory,$name,'local');
        abort_unless($path,500,'파일 저장에 실패했습니다.');
        return ['original_filename'=>basename(str_replace('\\','/',$file->getClientOriginalName())),
            'stored_filename'=>$name,'storage_path'=>$path,'mime_type'=>$file->getMimeType() ?: 'application/octet-stream','file_size'=>$file->getSize()];
    }
    public function resolveAlternative(string $relative): string {
        // Lab containment is independent from switchable application controls.
        abort_if(str_contains($relative,"\0") || str_contains($relative,':'),403);
        $root=realpath(Storage::disk('local')->path(''));
        $path=$root ? realpath($root.DIRECTORY_SEPARATOR.$relative) : false;
        abort_unless($root && $path && str_starts_with($path,$root.DIRECTORY_SEPARATOR) && is_file($path),403);
        return $path;
    }
    public function resolve(string $relative): string {
        $root=realpath(Storage::disk('local')->path(''));
        try { $path=realpath(Storage::disk('local')->path($relative)); }
        catch (\League\Flysystem\PathTraversalDetected $e) { abort(404); }
        abort_unless($root && $path && str_starts_with($path,$root.DIRECTORY_SEPARATOR) && is_file($path),404);
        return $path;
    }
}
