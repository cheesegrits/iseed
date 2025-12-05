<?php

namespace Cheesegrits\Iseed\Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    protected function getPackageProviders($app)
    {
        return [
        ];
    }
    
//    /**
//     * Creates the application.
//     *
//     * @return \Illuminate\Foundation\Application
//     */
//    public function createApplication()
//    {
//        $app = require __DIR__.'/../bootstrap/app.php';
//
//        $app->make(Kernel::class)->bootstrap();
//
//        return $app;
//    }

    public function getEnvironmentSetUp($app)
    {
        return $app;
    }
}
