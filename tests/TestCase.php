<?php
namespace Tests;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
abstract class TestCase extends BaseTestCase {
    public function createApplication() {
        $app=parent::createApplication();
        if (config('database.connections.mariadb.database')!=='intravault_testing' || config('database.connections.lab_write.database')!=='intravault_testing_lab') {
            throw new \RuntimeException('Refusing tests outside the isolated test databases.');
        }
        return $app;
    }
}
