<?php
namespace Database\Factories;
use App\Models\{User,Department};
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
class DocumentFactory extends Factory {
    public function definition(): array {
        $name=Str::uuid().'.txt';
        return ['title'=>'테스트 문서 '.fake()->numerify('####'),'description'=>'합성 검증 자료','original_filename'=>'fixture.txt',
            'stored_filename'=>$name,'storage_path'=>'documents/'.$name,'mime_type'=>'text/plain','file_size'=>128,
            'uploader_id'=>User::factory(),'department_id'=>Department::factory(),'security_level'=>'department'];
    }
}
