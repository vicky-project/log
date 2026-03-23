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
        "icon" => "bi bi-bug",
        "type" => "dropdown",
        "order" => 50,
        "children" => [
          $this->item([
            "title" => "Apps Log",
            "icon" => "bi bi-app",
            "route" => "admin.logs.app",
            "order" => 1,
            "permission" => Permission::VIEW_APP_LOGS,
          ]),
        ],
      ]),
    ];
  }
}