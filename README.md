# Prestige Wheels — Car Hire System

A professional, full-stack Car Hire management system built using PHP, MySQL (PDO), and vanilla CSS/JS. Optimized for serverless deployment on Vercel.

**Live Link:** [https://car-hire-system.vercel.app](https://car-hire-system.vercel.app)

---

## 🚀 Key Features

* **User Authentication:** Complete secure sign-up, login, password recovery, and password reset flows.
* **Car Management:** Admin panel to add, edit, and delete vehicles.
* **Booking & Checkout:** Seamless booking workflow, checking fleet availability, and generating transactional receipts.
* **GPS Tracking:** Real-time coordinate tracking and location mapping simulation for hire cars.
* **Document Verification:** Secure customer portal to upload driver licenses/IDs before hiring.
* **M-Pesa Integration:** Digital mobile payment processing and verification support.
* **Responsive Dashboard:** Modern client and admin dashboards built with clean CSS animations and responsive grid structures.

---

## 🛠️ Tech Stack

* **Frontend:** HTML5, JavaScript, Custom Vanilla CSS
* **Backend:** PHP (modular design)
* **Database:** MySQL via PDO (with SSL support for secure remote connections like Aiven)
* **Serverless Deployment:** Vercel (using the community `vercel-php` runtime)

---

## ⚙️ Local Development

1. Clone this repository into your local web server directory (e.g., `xampp/htdocs/Car_Hire_System`).
2. Import the database schema from `backend/database.sql` into your local MySQL server.
3. Configure database credentials in `backend/db.php` or set the local environment variables.
4. Access the system via `http://localhost/Car_Hire_System`.

