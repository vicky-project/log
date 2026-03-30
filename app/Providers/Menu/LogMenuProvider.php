<?php
namespace Modules\Log\Providers\Menu;

use Modules\Log\Constants\Permission;
use Modules\CoreUI\Services\BaseMenuProvider;

class LogMenuProvider extends BaseMenuProvider
{
  protected array $config = [
    "group" => "server",
    "location" => "sidebar",
    "icon" => "bi bi-server",
    "order" => 2,
    "permission" => null,
  ];

  public function __construct() {
    $moduleName = "Log";
    parent::__construct($moduleName);
  }

  /**
  * Get all menus
  */
  public function getMenus(): array
  {
    return [
      $this->item([
        "title" => "Log Management",
        "icon" => "bi bi-file-text-fill",
        "type" => "dropdown",
        "order" => 50,
        "children" => [
          $this->item([
            "title" => "Activity Log",
            "icon" => "bi bi-activity",
            "route" => "admin.logs.activity",
            "order" => 1,
            "permission" => Permission::VIEW_ACTIVITY_LOGS,
          ]),
          $this->item([
            "title" => "Apps Log",
            "icon" => "bi bi-app",
            "route" => "admin.logs.app",
            "order" => 2,
            "permission" => Permission::VIEW_APP_LOGS,
          ]),
          $this->item([
            "title" => "Auth Log",
            "icon" => "bi bi-door-open",
            "route" => "admin.logs.auth",
            "order" => 3,
            "permission" => Permission::VIEW_AUTH_LOGS,
          ]),
          $this->item([
            "title" => "Scheduler",
            "icon" => "bi bi-calendar-check",
            "route" => "admin.schedule-monitor.index",
            "order" => 4,
            "permission" => Permission::VIEW_SCHEDULE_MONITOR,
          ]),
        ],
      ]),
    ];
  }
}