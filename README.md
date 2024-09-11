# ISPConfig - Nextcloud Plugin

Allows users to log in to **Nextcloud** with their email or custom login name created in the **ISPConfig 3** Control Panel.  

**IMPORTANT:** This plugin requires the installation of an APP in **Nextcloud**. [Nextcloud - User ISPConfig API](https://github.com/mediabox-cl/nextcloud-user-ispconfig-api.git)

## Features

This plugin has three places where you can configure different functionalities or integrations with **Nextcloud**.

- `System > Server Config > Your Server Name > Nextcloud (TAB)`
- `Email > Domain > Your Domain Name > Nextcloud (TAB)`
- `Email > Email Mailbox > Your Mailbox Name > Nextcloud (TAB)`

### Users

- Users can change their **ISPConfig** password and display name in the **Nextcloud** interface.
- Delete the **Nextcloud** user account and data when the **ISPConfig** Mailbox User or Domain is deleted.
- Enable/Disable login by user or domain.
- You can define the user quota per domain or per user.
- ...

### Groups

Groups have 3 places where you can set them; Server, Domain and User.
- The Server Group is like a Global Group for all the mail users in this server.
- The Domain Group is a Group for all the Users ho belong to each domain.
- The User Group are specific for each user.
- Add or remove users to the group defined in Server, Domain and User.
- Add or remove users as admin of the groups they belong to.
- Delete empty group.
- ...

## Installation

### Manual installation

Installation in **ISPConfig** is a bit tricky because this control panel, despite being very good, is not very user-friendly when it comes to creating/installing plugins.

**IMPORTANT!:** The DATABASE user "ispconfig", have the `SELECT`, `INSERT`, `UPDATE` and `DELETE` privileges only, but this plugin requires the `ALTER` privilege.

**Before** you install this plugin, you must log in to your **phpMyAdmin** (easier) and give the `ALTER` privilege to the "ispconfig" user in the "dbispconfig" database.

_Notes:_

- _After finishing the installation of the plugin you can disable the `ALTER` privilege._
- _"dbispconfig" and "ispconfig" are the default database and database user for **ISPConfig**. You may have another one if you changed it during installation._

Login to your server and clone this repository:

```bash
cd /tmp
git clone https://github.com/mediabox-cl/ispconfig-nextcloud-plugin.git
cd ispconfig-nextcloud-plugin
cp -R interface /usr/local/ispconfig
cp -R server /usr/local/ispconfig
chown -R ispconfig:ispconfig /usr/local/ispconfig/interface/lib
cd /usr/local/ispconfig/server/plugins-enabled
ln -s /usr/local/ispconfig/server/plugins-available/nextcloud_plugin.inc.php nextcloud_plugin.inc.php
rm -rf /tmp/ispconfig-nextcloud-plugin
```

Logout from your **ISPConfig 3** Control Panel and login again. **You must do this!**  
Now you can navigate to the **Nextcloud** tabs in the Server, Domain and Mailbox and make your configurations.

## Update

### Manual Update

**Before** update, you must log in to your **phpMyAdmin** (easier) and give the `ALTER` privilege to the "ispconfig" user in the "dbispconfig" database.

_Notes:_

- _After finishing the update you can disable the `ALTER` privilege._
- _"dbispconfig" and "ispconfig" are the default database and database user for **ISPConfig**. You may have another one if you changed it during installation._

Login to your server and clone this repository:

```bash
cd /tmp
git clone https://github.com/mediabox-cl/ispconfig-nextcloud-plugin.git
cd ispconfig-nextcloud-plugin
cp -R interface /usr/local/ispconfig
cp -R server /usr/local/ispconfig
chown -R ispconfig:ispconfig /usr/local/ispconfig/interface/lib
rm -rf /tmp/ispconfig-nextcloud-plugin
```

Logout from your ISPConfig 3 Control Panel and login again. You must do this!

## Configuration

### Nextcloud API

This plugin uses the **Nextcloud API** to delete users on **Nextcloud** who were deleted in **ISPConfig**.

- Login to your **Nextcloud** installation with your admin account.
- Navigate to `Personal settings > Security (Devices & sessions)` and create a new `APP Password`.
- Input the APP name, for example: `ispconfig` and hit the `Create new app password` button.
- Copy and paste the supplied credentials in `ISPConfig > Server > Nextcloud (TAB)`.

Now, whenever you delete a user or domain in **ISPConfig**, this user or domain users will be deleted in **Nextcloud** (if enabled).

### What's next?

Please, follow the instruction here to install the [Nextcloud - User ISPConfig API](https://github.com/mediabox-cl/nextcloud-user-ispconfig-api.git) APP.

## Thanks to:

- Till Brehm from Projektfarm GmbH.
- Falko Timme from Timme Hosting.
- The ISPConfig community and developers.
- The Nextcloud community and developers.
