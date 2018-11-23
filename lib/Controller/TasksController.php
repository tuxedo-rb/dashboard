<?php
/**
 * Nextcloud - Dashboard App
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tuxedo-rb
 * @copyright TUXEDO Computers GmbH
 * @license GNU AGPL version 3 or any later version
 * @contributor tuxedo-rb | TUXEDO Computers GmbH | https://www.tuxedocomputers.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Dashboard\Controller;

use OCA\Dashboard\Service\TasksService;
use OCA\Dashboard\Service\DashboardService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * Description of TasksController
 *
 */
class TasksController extends Controller {

	const TASKS_DUE_DISPLAY_LIMIT = 6;
	const TASKS_NEW_DISPLAY_LIMIT = 6;

	/** @var DashboardService */
	private $dashboardService;

	/** @var TasksService */
	private $tasksService;

	private $data;

	/**
	 * TasksController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param DashboardService $dashboardService
	 * @param TasksService $tasksService
	 *
	 * @internal param $userId
	 * @internal param GroupHelper $myGroupHelper
	 * @internal param UserSettings $userSettings
	 */
	public function __construct(
		$appName,
		IRequest $request,
		DashboardService $dashboardService,
		TasksService $tasksService
	) {
		parent::__construct($appName, $request);
		$this->dashboardService = $dashboardService;
		$this->tasksService = $tasksService;
		$this->data = $this->tasksService->getTasksFromCalendar();
		
	}

	/**
	 * load Tasks
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return DataResponse
	 */
	public function indexDue() {
		$tasksDueData = $this->tasksService->getNextDueTasks(
			$this->data,
			static::TASKS_DUE_DISPLAY_LIMIT
		);
		return new DataResponse(['data' => $tasksDueData]);
	}

	/**
	 * load Tasks
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return DataResponse
	 */
	public function indexNew() {
		$tasksNewData = $this->tasksService->getLatestCreatedTasks(
			$this->data,
			static::TASKS_NEW_DISPLAY_LIMIT
		);
		return new DataResponse(['data' => $tasksNewData]);
	}

}
