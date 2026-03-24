<?php
namespace Modules\Log\Installations;

use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Facades\Artisan;

class PostInstallation
{
  public function handle(string $moduleName) {
    try {
      $modules = array_merge(["users"], [$moduleName]);
      foreach ($modules as $modulename) {
        $module = Module::find($modulename);
        $module->enable();
      }

    } catch (\Exception $e) {
      logger()->error(
        "Failed to run post installation of log module: " .
        $e->getMessage(),
      );

      throw $e;
    }
  }
}