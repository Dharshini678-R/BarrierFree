# BarrierFree — Accessibility Barrier Mapper

Local PHP + MySQL app for reporting accessibility barriers in public places. It uses the existing `barrierfree_db` schema (users, reports, comments, status history, admin notes, and feedback).

## What you need

- Windows with **XAMPP** (this project was built against Apache + PHP 8.2 and MariaDB in XAMPP).
- The project folder at `C:\xampp_new\htdocs\BarrierFree` (or any Apache `htdocs` folder named `BarrierFree`).
- XAMPP **MariaDB/MySQL on port 3307**.
- Do **not** stop or reconfigure the Windows **MySQL80** service on port **3306**. This app does not use 3306.

## Database

The application connects to:

| Setting | Value |
|---|---|
| Host | `127.0.0.1` |
| Port | `3307` |
| Database | `barrierfree_db` |
| User | `root` |
| Password | empty (local XAMPP default) |

Settings live in `database.php`. Change only that file if your local root password is not empty.

`database.sql` creates the database and tables with `CREATE TABLE IF NOT EXISTS`. It does **not** drop tables. If `barrierfree_db` already exists (it should), you do not need to import again.

If tables are missing, import once from phpMyAdmin:

1. Open `http://localhost/phpmyadmin/`
2. Confirm phpMyAdmin is talking to the XAMPP MariaDB instance (port 3307), not MySQL80.
3. Select `barrierfree_db` or import `database.sql` from this folder.

## Run the site

1. Start **Apache** and **MySQL** in the XAMPP Control Panel. MySQL here is the XAMPP service bound to **3307**.
2. Open:

   **http://localhost/BarrierFree/**

3. Create an account at **Create account**, then submit reports.

New accounts are always stored with role `user`. The form cannot grant administrator access.

### Make an administrator

In phpMyAdmin (MariaDB on 3307) or the MySQL client:

```sql
USE barrierfree_db;
UPDATE users SET role = 'admin' WHERE username = 'YOUR_USERNAME';
```

Then sign out and sign in again. Admin pages are under `admin/`.

## Pages

- `index.php` — landing page
- `signup.php` / `login.php` / `logout.php` — accounts
- `report.php` — submit a barrier (signed-in users)
- `reports.php` — browse and filter
- `report_details.php` — report, comments, status history
- `dashboard.php` / `profile.php` — your reports and account
- `admin/dashboard.php`, `admin/reports.php`, `admin/report.php` — review and status changes

## Photos

Optional JPEG, PNG, or WebP uploads, 3 MB or smaller, stored in `uploads/reports/`. PHP files cannot be executed from that folder.

The map picker uses OpenStreetMap tiles in the browser. If you are offline, type latitude and longitude instead.

## Original helper files

Copies of the files that were already in the project are in `backups/original/`.
