<?php
/*
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

namespace OCA\Dashboard\Migration;

use OCP\Migration\IRepairStep;
use OCP\Migration\IOutput;
use OCA\Dashboard\Db\DashboardSettings;
use OCA\Dashboard\Db\DashboardSettingsMapper;
use OCP\IConfig;

/**
 * Description of AddTasksDashboardSettings
 * adds new settings entries for tasks sections
 */
class AddTasksDashboardSettings implements IRepairStep {

	/** @var DashboardSettings */
	private $dashboardSettings;

	/** @var DashboardSettingsMapper */
	private $dashboardSettingsMapper;

	/** @var IConfig */
	private $config;

	/**
	 * @param DashboardSettings $dashboardSettings
	 * @param DashboardSettingsMapper $dashboardSettingsMapper
	 * @param IConfig $config
	 */
	public function __construct(
		DashboardSettings $dashboardSettings,
		DashboardSettingsMapper $dashboardSettingsMapper,
		IConfig $config
	) {
		$this->dashboardSettings = $dashboardSettings;
		$this->dashboardSettingsMapper = $dashboardSettingsMapper;
		$this->config = $config;
	}

	public function getName() {
		return 'set default Task section Dashboard settings';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		if ($this->config->getAppValue('dashboard', 'addTasksDashboardSettings') === 'yes') {
			$output->info('default Task section Dashboard settings already saved');
			return;
		}

		$this->dashboardSettings->setId(14);
		$this->dashboardSettings->setKey('show_tasks_due');
		$this->dashboardSettings->setValue(1);
		$this->dashboardSettingsMapper->insertOrUpdate($this->dashboardSettings);

		$this->dashboardSettings->setId(15);
		$this->dashboardSettings->setKey('show_wide_tasks_due');
		$this->dashboardSettings->setValue(0);
		$this->dashboardSettingsMapper->insertOrUpdate($this->dashboardSettings);

		$this->dashboardSettings->setId(16);
		$this->dashboardSettings->setKey('tasks_due_position');
		$this->dashboardSettings->setValue(5);
		$this->dashboardSettingsMapper->insertOrUpdate($this->dashboardSettings);

		$this->dashboardSettings->setId(17);
		$this->dashboardSettings->setKey('show_tasks_new');
		$this->dashboardSettings->setValue(1);
		$this->dashboardSettingsMapper->insertOrUpdate($this->dashboardSettings);

		$this->dashboardSettings->setId(18);
		$this->dashboardSettings->setKey('show_wide_tasks_new');
		$this->dashboardSettings->setValue(0);
		$this->dashboardSettingsMapper->insertOrUpdate($this->dashboardSettings);

		$this->dashboardSettings->setId(19);
		$this->dashboardSettings->setKey('tasks_new_position');
		$this->dashboardSettings->setValue(6);
		$this->dashboardSettingsMapper->insertOrUpdate($this->dashboardSettings);

		$this->config->setAppValue('dashboard', 'addTasksDashboardSettings', 'yes');

		$output->info("initial default Task section Dashboard settings saved");
	}

}
