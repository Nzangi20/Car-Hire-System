# COSC236 Individual Project — Car Hire System

Project title: Car Hire System

Student name: Ngwambu Nzangi  
Registration number: EB1/66879/23  
Course: COSC326  
Date submitted: 2025-12-22

## Project summary
This is a web-based Car Hire System implemented with PHP and MySQL (XAMPP).  
Features:
- User registration and login (collects full name and national ID/passport).
- Users can browse cars, view images, hire cars, and view their bookings.
- Admin pages to add/edit cars (with image upload).
- All images are stored in `car_photos/` and referenced in the database.

## Folder structure (what to include in ZIP)
- `dashboard.php`, `register.php`, `login.php`, `hirecar.php`, etc.
- `car_photos/` — all car image files referenced by the `photo` column in `cars` table.
- `database.sql` — SQL dump of database schema and sample data (created by this script).
- `README.md` (this file)

## How to run locally (XAMPP)
1. Place the project folder inside XAMPP `htdocs`:
   - Windows: `C:\xampp\htdocs\<PROJECT_FOLDER>\`
2. Start Apache and MySQL using XAMPP Control Panel.
3. Import the database:
   - Open phpMyAdmin → create a new database (e.g., `pestige_wheels`) → Import `database.sql`.
4. Update DB connection credentials in your config (if required).
5. Open in browser:
   `http://localhost/<PROJECT_FOLDER>/dashboard.php`

## Academic integrity
I declare that this project is my own work and that I have not copied from others except where explicit collaboration is permitted.

Signed: Ngwambu Nzangi