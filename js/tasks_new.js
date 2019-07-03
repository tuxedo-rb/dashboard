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

(function ($, OC) {
    $(document).ready(function () {
        $('#tasks_new').find('> table').DataTable(
            {
                // DataTables - Features
                info: false,
                paging: false,
                searching: false,
                // DataTables - Data
                ajax: OC.generateUrl('/apps/dashboard/tasks_new'),
                // DataTables - Options
                order: [[4, 'desc']],
                // DataTables - Columns
                columns: [
                    {
                        data: function (row) {
                            return '<a href="' + OC.generateUrl('/apps/tasks/#/calendars/' + row.uri) + '">' + row.task + '</a>';
                        }
                    },
                    {
                        data: function (row) {
                            var gaga = 0;
                            if (Number(row.priority) !== 0) {
                                gaga = 10 - row.priority;
                            }
                            var title = 'None';
                            var star = 'icon-task-star icon_task-star-none';
                            if (gaga > 5) {
                                star = 'icon-task-star-high';
                                title = 'High';
                            } else if (gaga === 5) {
                                star = 'icon-task-star-medium';
                                title = 'Medium';
                            } else if (gaga > 0) {
                                star = 'icon-task-star-low';
                                title = 'Low';
                            }
                            return '<div title="'
                                + title
                                + '">'
                                + gaga
                                + ' <span class="taskstars '
                                + star
                                + '"></span></div>';
                        }
                    },
                    {
                        data: function (row) {
                            var htmlStr =
                                '<div id="task-progress" class="myCollapse in">'
                                + '<div id="quota-used" style="width: '
                                + row.progress
                                + '%;"></div>'
                                + '<div id="quota-limit"></div>'
                                + '<p id="quota-text"><strong>'
                                + row.progress
                                + ' %'
                                + '</strong></p>'
                                + '</div>'
                                + '<div style="clear: both;"></div>';
                            return htmlStr;
                        }
                    },
                    {
                        data: function (row) {
                            var today = new Date().toISOString();
                            var due = 'due';
                            if (today > row.due) {
                                due = 'overdue';
                            }
                            return '<span id="'
                                + due
                                + '">'
                                + row.due
                                + '</span>';
                        }
                    },
                    {
                        data: function (row) {
                            var formattedDate = '';
                            formattedDate = row.created.substr(0, 4)
                                + '-'
                                + row.created.substr(4, 2)
                                + '-'
                                + row.created.substr(6, 2);
                            return '<span class=\"hidden\">'
                                + row.created
                                + '</span>'
                                + formattedDate;
                        }
                    }
                ]
            }
        );
    });
})
(jQuery, OC);
