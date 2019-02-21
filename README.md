![](https://raw.githubusercontent.com/tuxedocomputers/dashboard/tasks_support/screenshots/nc-dashboard_tasks-sections.png)

## Requirements and Dependencies

The Dashboard App is dependent on Activity App, Calendar App, Tasks App and optional on
Rainloop App (a click on a displayed email leads to the Rainloop App).

The inbox section of the Dashboard requires the installation of the
php extension imap [http://php.net/manual/en/book.imap.php](http://php.net/manual/en/book.imap.php).

On two test servers with Ubuntu 16.04.4 and CentOS 7.4.1708
imap ran out of the box after installation and a reboot (both php7).
But some distributions perhaps require enabling or/and further configuration of
imap.


## Installation

### Preparation

Disable and remove the previous version of the Dashboard App through the
admin backend.

There are two ways to install the Dashboard App, following described under
`Installation per git clone` and `Installation per archive download`.
Choose your preferred method.


### Installation per git clone

Open a terminal and clone the Dashboard App with following commands into your
nextcloud/apps/ folder:
```
cd [path to your webroot]/nextcloud/apps/
```
```
git clone --branch tasks_support --single-branch https://github.com/tuxedocomputers/dashboard.git
```

Install 3rd party scripts via composer [https://getcomposer.org/](https://getcomposer.org/).
```
cd [path to your webroot]/nextcloud/apps/dashboard/
```
```
composer.phar install
```

Open a terminal and change the ownership of the whole dashboard directory to
your webservice user and group (usually `www-data` or `apache`):
```
chown -R www-data:www-data [path to your webroot]/nextcloud/apps/dashboard
```

Finally, enable the Dashboard App through the admin backend.


### Installation per archive download

Download the archive from:
[https://github.com/tuxedocomputers/dashboard/archive/tasks_support.zip](https://github.com/tuxedocomputers/dashboard/archive/tasks_support.zip)

Open a terminal, unzip the archive into your nextcloud/apps/ folder and
rename the dashboard-tasks_support folder to dashboard: 
```
cd [path to downloaded archive]
```
```
unzip dashboard-tasks_support.zip -d [path to your webroot]/nextcloud/apps/
```
```
mv [path to your webroot]/nextcloud/apps/dashboard-tasks_support [path to your webroot]/nextcloud/apps/dashboard
```

Install 3rd party scripts via composer [https://getcomposer.org/](https://getcomposer.org/).
```
cd [path to your webroot]/nextcloud/apps/dashboard
```
```
composer.phar install
```

Open a terminal and change the ownership of the whole dashboard directory to
your webservice user and group (usually `www-data` or `apache`):
```
chown -R www-data:www-data [path to your webroot]/nextcloud/apps/dashboard
```

Finally, enable the Dashboard App through the admin backend.


## App Configuration

It is possible to limit access to the Dashboard App to a certain group of users.
Simply activate the `Limit to groups` checkbox and choose the desired
user-group in the Apps overview.

Visibility, positioning and proportioning of the 7 main dashboard sections
are configurable under `Administration` -> `Dashboard`.

The individual Email settings for each user are configurable under `Personal` ->
`Dashboard`.


## Notes

- to be able to create/edit/delete announcements, the user has to be a member
 of user-group `News`

- this user-group is hardcoded in following files:
 `./dashboard/lib/Controller/AnnouncementController.php`
 and
 `./dashboard/lib/Controller/PageController.php`

- a disabled dashboard section in the dashboard admin settings should have a
 unique placement number, even if it should not displayed
 (don't use the same placement number several times)

- the link to the rainloop email app is hardcoded in following file:
 `./dashboard/js/inbox.js`
