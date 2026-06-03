# Enrollment System - Installation Manual

## System Name

**enrollment_system**

---

## 1. Download the Project

Download the ZIP file from:

```bash
https://github.com/halimeow16/enrollment_system
```

Or clone the repository using Git:

```bash
git clone https://github.com/halimeow16/enrollment_system.git
```

---

## 2. Requirements

Make sure the following software is installed on your computer:

* Node.js
* Composer
* XAMPP
* PHP 8.3 or higher

---

## 3. Configure PHP Extensions in XAMPP

Before installing dependencies, open the `php.ini` file located in your XAMPP PHP directory.

Example:

```text
C:\xampp\php\php.ini
```

### Steps

1. Search for:

```ini
extension=zip
```

2. If it appears as:

```ini
;extension=zip
```

remove the semicolon (`;`).

3. If `extension=zip` does not exist, add it manually.

4. Save the file.

5. Restart Apache if necessary.

---

## 4. Install Project Dependencies

Open Command Prompt (CMD) and navigate to the project folder:

```bash
cd path\to\enrollment_system
```

Run the following commands one by one:

```bash
composer install
npm install
npm run build
copy .env.example .env
php artisan key:generate
php artisan storage:link
```

---

## 5. Configure the Environment File

Open the `.env` file and review the database configuration settings.

Example:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=enrollment_system
DB_USERNAME=root
DB_PASSWORD=
```

### Notes

* If your MySQL database uses a password, update the `DB_PASSWORD` value accordingly.
* If there is no password configured, leave the default value as it is.

---

## 6. Start XAMPP Services

Open the **XAMPP Control Panel** and start the following services:

* Apache
* MySQL

---

## 7. Create Database Tables and Seed Data

After Apache and MySQL are running, execute:

```bash
php artisan migrate:fresh --seed
```

This command will:

* Create all database tables
* Run migrations
* Insert default seed data

---

## Included Utility

The project includes a **start_server** shortcut that automatically starts the Laravel application server.

This eliminates the need to manually execute:

```bash
php artisan serve
```

Recommended for:

* First-time users
* Non-technical staff
* Quick deployment and testing

---

## 8. Run the Application

After installation and database setup are complete:

### Steps

1. Locate the **start_server** shortcut included with the project.
2. Double-click **start_server**.
3. Wait for the server window to open and initialize the application.
4. Open your web browser and visit:

#### Admin Portal

```text
http://127.0.0.1:8000/office
```

#### Enrollment Portal

```text
http://127.0.0.1:8000/enrollment
```

5. The Enrollment System should now be accessible.

> **Note:** Ensure that Apache and MySQL are running in XAMPP before launching the application.

---

# Troubleshooting

### PHP Version Issues

* Ensure PHP 8.3 or higher is installed and active in XAMPP.

### Missing Extensions

Verify that the following PHP extensions are enabled:

* ZIP
* MySQL (mysqli / pdo_mysql)

### Apache or MySQL Not Running

* Open XAMPP Control Panel.
* Confirm that both Apache and MySQL services are running.

### Database Connection Errors

* Recheck database credentials in the `.env` file.
* Confirm the database exists.
* Verify the username and password are correct.

### Migration Failures

Run:

```bash
php artisan migrate:fresh --seed
```

again after correcting the database configuration.

### Missing Vendor Dependencies

If Laravel reports missing dependencies, run:

```bash
composer install
```

again.

### Frontend Build Issues

If assets fail to load, rebuild them:

```bash
npm install
npm run build
```

---

# Support

If you encounter installation issues, verify all requirements are installed correctly and review the troubleshooting section above before seeking additional support.
