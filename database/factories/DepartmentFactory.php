<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
class DepartmentFactory extends Factory {
    public function definition(): array { return ['name'=>'테스트 부서 '.fake()->unique()->numerify('####'),'code'=>fake()->unique()->bothify('T-????##'),'description'=>'테스트 전용']; }
}
