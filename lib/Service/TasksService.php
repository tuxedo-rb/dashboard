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

namespace OCA\Dashboard\Service;

use OCP\IConfig;
use OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Description of TasksService
 *
 */
class TasksService {

	const TASKS_DUE_DAY_LIMIT = 7;

	/** @var string */
	private $userId;

	/** @var IDBConnection */
	protected $connection;

	/** @var IConfig */
	private $config;

	/**
	 * TasksService constructor.
	 *
	 * @param string $userId
	 * @param IDBConnection $connection
	 * @param IConfig $config
	 */
	public function __construct($userId, IDBConnection $connection, IConfig $config) {
		$this->userId = $userId;
		$this->connection = $connection;
		$this->config = $config;
	}

	/**
	 * 
	 * @return array
	 */
	private function getGroupPrincipaluri() {
		$groupId = 'gid';
		$tableName = 'group_user';
		$user = 'uid';
		$queryBuilder = $this->connection->getQueryBuilder();
		// support for ldap
		if ($this->config->getAppValue('user_ldap', 'enabled') === 'yes') {
			// SELECT owncloudname AS gid FROM [prefix]_ldap_group_members
			$queryBuilder->select('owncloudname AS gid')->from('ldap_group_members');
			// WHERE owncloudusers LIKE '%:"[current user]";%'
			$queryBuilder->where(
				$queryBuilder->expr()->like(
					'owncloudusers',
					$queryBuilder->createNamedParameter(
						'%:"' . $this->userId . '";%'
					)
				)
			);
		} else {
			// SELECT gid FROM [prefix]_group_user
			$queryBuilder->select('gid')->from('group_user');
			// WHERE uid = [current user]
			$queryBuilder->where(
				$queryBuilder->expr()->eq(
					'uid',
					$queryBuilder->createNamedParameter(
						$this->userId
					)
				)
			);
		}
		$result = $queryBuilder->execute();

		$groupIds = array();
		while ($row = $result->fetch()) {
			$groupIds[] = 'principals/groups/' . $row['gid'];
		}

		$result->closeCursor();

		return $groupIds;
	}

	/**
	 * 
	 * @return array
	 */
	private function getSharedCalendarIds() {
		$groups = $this->getGroupPrincipaluri();
		$queryBuilder = $this->connection->getQueryBuilder();
		// SELECT resourceid FROM [prefix]_dav_shares
		$queryBuilder->select('resourceid')->from('dav_shares');
		// WHERE
		$queryBuilder->where(
			$queryBuilder->expr()->orX(
		// principaluri IN ([result array of getGroupPrincipaluri]) OR principaluri = 'principals/users/[current user]'
				$queryBuilder->expr()->in(
					'principaluri',
					$queryBuilder->createNamedParameter(
						$groups,
						IQueryBuilder::PARAM_STR_ARRAY
					)
				),
				$queryBuilder->expr()->eq(
					'principaluri',
					$queryBuilder->createNamedParameter(
						'principals/users/'
						. $this->userId
					)
				)
			)
		);
		$result = $queryBuilder->execute();

		$sharedCalendarIds = array();
		while ($row = $result->fetch()) {
			$sharedCalendarIds[] = $row['resourceid'];
		}

		$result->closeCursor();

		return array_unique($sharedCalendarIds);
	}

	/**
	 * get all open tasks from calendar tables
	 * 
	 * @return array
	 */
	public function getTasksFromCalendar() {
		$calendarIds = $this->getSharedCalendarIds();
		$queryBuilder = $this->connection->getQueryBuilder();
		// SELECT * FROM [prefix]_calendarobjects AS co
		$queryBuilder->select('*')->from('calendarobjects', 'co');
		// JOIN [prefix]_calendars AS c ON co.calendarid = c.id
		$queryBuilder->innerJoin(
			'co',
			'calendars',
			'c',
			'co.calendarid = c.id'
		);
		// WHERE
		$queryBuilder->where(
			$queryBuilder->expr()->andX(
				$queryBuilder->expr()->orX(
		// (co.calendarid IN ([result array of getSharedCalendarIds]) OR c.principaluri = 'principals/users/[current user]')
					$queryBuilder->expr()->in(
						'co.calendarid',
						$queryBuilder->createNamedParameter(
							$calendarIds,
							IQueryBuilder::PARAM_STR_ARRAY
						)
					),
					$queryBuilder->expr()->eq(
						'c.principaluri',
						$queryBuilder->createNamedParameter(
							'principals/users/'
							. $this->userId
						)
					)
				),
		// AND co.componenttype = 'VTODO'
				$queryBuilder->expr()->eq(
					'co.componenttype',
					$queryBuilder->createNamedParameter(
						'VTODO'
					)
				),
		// AND co.calendardata NOT LIKE '%STATUS:COMPLETED%'
				$queryBuilder->expr()->notLike(
					'co.calendardata',
					$queryBuilder->createNamedParameter(
						'%STATUS:COMPLETED%'
					)
				)
			)
		);

		$result = $queryBuilder->execute();

		$tasks = array();
		// note: the BEGIN and END entry may appears more than one times
		// at example: BEGIN:VCALENDAR and additionally BEGIN:VTODO
		// these aren't relevant for us, so no special treatment
		while ($row = $result->fetch()) {
			$calendarData = preg_split(
				'/[\r\n]+/',
				$row['calendardata']
			);
			$tasksData = array();
			$lastKey = '';
			foreach ($calendarData as $dataEntry) {
				// what a friggin mess ...
				if (preg_match('/^[\s]/', $dataEntry) === 0) {
					$tmp = explode(':', $dataEntry, 2);
				} else {
					unset($tmp[1]);
					$tmp[0] = $dataEntry;
				}
				$search = array('\,', '\;');
				$replace = array(',', ';');
				// multiline workaround for SUMMARY and DESCRIPTION
				if (!isset($tmp[1])) {
					// remove leading space char and unescape chars
					$tasksData[$lastKey] =
						$tasksData[$lastKey]
						. str_replace(
							$search,
							$replace,
							substr($tmp[0], 1)
						);
				} else {
					// unescape chars
					$tasksData[$tmp[0]] = str_replace($search, $replace, $tmp[1]);
					$lastKey = $tmp[0];
				}
			}
			$tmpOwner = explode('/', $row['principaluri']);
			$uri = $row['uri'];
			if ($tmpOwner[sizeof($tmpOwner) -1] !== $this->userId) {
			    $uri .= "_shared_by_" . $tmpOwner[sizeof($tmpOwner) -1];
			}
			$tmpDue = 'DUE';
			if (isset($tasksData['DUE;VALUE=DATE'])) {
				$tmpDue = 'DUE;VALUE=DATE';
			}
			$dueDate = '';
			if (isset($tasksData[$tmpDue])) {
				$dueDate = substr($tasksData[$tmpDue], 0, 4)
					. '-'
					. substr($tasksData[$tmpDue], 4, 2)
					. '-'
					. substr($tasksData[$tmpDue], 6, 2);
			}
			$tasks[] = array(
				'task'     => $tasksData['SUMMARY'],
				'uri'      => $uri,
				'priority' => isset($tasksData['PRIORITY']) ? $tasksData['PRIORITY'] : '0',
				'progress' => isset($tasksData['PERCENT-COMPLETE']) ? $tasksData['PERCENT-COMPLETE'] : '0',
				'created'  => $tasksData['CREATED'],
				'due'      => $dueDate,
			);
		}

		$result->closeCursor();

		return $this->resultSort($tasks, 'created', TRUE);
	}

	/**
	 * orders $resArray by $index
	 * $reverse affects ascending or descending order (default FALSE = asc)
	 * 
	 * @param array $resArray
	 * @param string $index
	 * @param boolean $reverse
	 * @return array
	 */
	private function resultSort($resArray, $index, $reverse = FALSE) {
		$hash = array();
		foreach($resArray as $entry) {
			$hash[$entry[$index]] = $entry;
		}

		if ($reverse === TRUE) {
			krsort($hash);
		} else {
			ksort($hash);
		}

		unset($entry);
		$sorted = array();
		foreach($hash as $entry) {
			$sorted[] = $entry;
		}

		return $sorted;
	}

	/**
	 * 
	 * @param string $date
	 * @return boolean
	 */
	private function dateCompare($date) {
		$todayPlusDayLimit = date(
			'Y-m-d',
			mktime(
				0,
				0,
				0,
				date("m"),
				date("d") + static::TASKS_DUE_DAY_LIMIT,
				date("Y")
			)
		);
		if ($todayPlusDayLimit >= $date) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 
	 * @param array $tasks
	 * @param int $limit
	 * @return array
	 */
	public function getNextDueTasks($tasks, $limit = 6) {
		$ascTasks = $this->resultSort($tasks, 'due');
		$nextTasks = array();
		$i = 0;
		foreach ($ascTasks as $task) {
			if ($i < $limit
				&& $task['due'] !== ''
				&& $this->dateCompare($task['due']) === TRUE
			) {
				$nextTasks[] = $task;
				$i++;
			} elseif ($i === $limit) {
				break;
			}
		}
		return $nextTasks;
	}

	/**
	 * 
	 * @param array $tasks
	 * @param int $limit
	 * @return array
	 */
// TODO: almost identical method
	public function getLatestCreatedTasks($tasks, $limit = 6) {
		$descTasks = $this->resultSort($tasks, 'created', TRUE);
		$lastTasks = array();
		$i = 0;
		foreach ($descTasks as $task) {
			if ($i < $limit) {
				$lastTasks[] = $task;
				$i++;
			} else {
				break;
			}
		}
		return $lastTasks;
	}

}
