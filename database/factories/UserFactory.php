<?php
namespace Database\Factories;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
class UserFactory extends Factory {
    protected static ?string $password;
    public function definition(): array {
        return ['employee_number'=>fake()->unique()->bothify('T-########'),'name'=>'테스트 사용자 '.fake()->numerify('####'),
            'email'=>fake()->unique()->safeEmail(),'password'=>static::$password ??= Hash::make('Test-only-password!'),
            'department_id'=>Department::factory(),'role'=>'employee','status'=>'active'];
    }
}
