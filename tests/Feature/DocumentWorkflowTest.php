<?php
namespace Tests\Feature;
use App\Models\Document;
use App\Services\DocumentFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Storage,Validator};
use Illuminate\Validation\ValidationException;
class DocumentWorkflowTest extends ProjectTestCase {
    public function test_document_crud_file_storage_share_and_confidential_upgrade(): void {
        $this->actingAs($this->employee)->post('/documents',[
            'title'=>'업무 자료','description'=>'검증용 문서','department_id'=>$this->department->id,'security_level'=>'department',
            'file'=>UploadedFile::fake()->createWithContent('report.txt','UNIQUE-FILE-MARKER')
        ])->assertRedirect();
        $doc=Document::where('title','업무 자료')->firstOrFail();
        $this->assertNotSame('report.txt',$doc->stored_filename);
        Storage::disk('local')->assertExists($doc->storage_path);
        $this->get(route('documents.download',$doc))->assertDownload('report.txt');
        $this->post(route('documents.share',$doc),['user_id'=>$this->other->id])->assertRedirect();
        $this->actingAs($this->manager)->put(route('documents.update',$doc),[
            'title'=>'기밀 전환','department_id'=>$this->department->id,'security_level'=>'confidential','description'=>'검증'
        ])->assertRedirect();
        $this->assertDatabaseMissing('document_shares',['document_id'=>$doc->id]);
        $this->actingAs($this->employee)->get(route('documents.show',$doc))->assertForbidden();
        $this->actingAs($this->admin)->delete(route('documents.destroy',$doc))->assertRedirect();
        $this->assertDatabaseMissing('documents',['id'=>$doc->id]);
        Storage::disk('local')->assertMissing($doc->storage_path);
        $this->assertDatabaseHas('audit_logs',['event'=>'document.delete','target_id'=>(string)$doc->id]);
    }
    public function test_file_extension_mime_and_40_mib_boundary(): void {
        $service=app(DocumentFiles::class);
        $service->validate(UploadedFile::fake()->create('limit.txt',40960,'text/plain'));
        foreach ([UploadedFile::fake()->create('over.txt',40961,'text/plain'),UploadedFile::fake()->create('renamed.pdf',2,'application/x-php'),UploadedFile::fake()->create('image.svg',2,'image/svg+xml')] as $file) {
            try { $service->validate($file); $this->fail('Unsafe upload accepted: '.$file->getClientOriginalName()); } catch (ValidationException $e) { $this->assertArrayHasKey('file',$e->errors()); }
        }
        $service->validate(UploadedFile::fake()->createWithContent('normal.txt','This is synthetic text.'));
    }
    public function test_allowed_file_extension_set_matches_rule(): void {
        $this->assertEqualsCanonicalizing(['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','zip','jpg','jpeg','png'],DocumentFiles::EXTENSIONS);
    }
    public function test_arbitrary_storage_path_in_database_is_not_served(): void {
        $doc=$this->document(); $doc->update(['storage_path'=>'../../../../.env']);
        $this->actingAs($this->admin)->get(route('documents.download',$doc))->assertNotFound();
    }
    public function test_guest_cannot_register_or_access_documents(): void {
        $this->get('/register')->assertNotFound();
        $this->post('/register',[])->assertNotFound();
        $this->get('/documents')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/storage/documents/fixture-1.bin')->assertNotFound();
    }
}
