<?php
namespace Database\Seeders;
use App\Models\LabFlag;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder {
    public function run(): void {
        foreach (array_keys(config('lab.vulnerabilities')) as $id) LabFlag::firstOrCreate(['id'=>$id],['enabled'=>false]);
    }
}
