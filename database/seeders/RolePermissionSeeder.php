<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Route;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $test = false;
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $action = $route->getAction();
            if (array_key_exists('as', $action)) {
                if ($action['as'] == "cms.services.destroy") {
                    $routes[] = $action['as'];
                    break;
                }
                if ($test)
                    $routes[] = $action['as'];
                else {
                    if ($action['as'] == "cms.auth.password.change") {
                        $test = true;
                    }
                }
            }
        }

        for ($i = 0; $i < sizeof($routes); $i++)
            Permission::create([
                'name' => $routes[$i]
            ]);
    }
}
