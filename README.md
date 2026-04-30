# 🌍 Eco-Friendly Travel Planner
### *Redefining the Way You Wander*

[![Database-MySQL](https://img.shields.io/badge/Database-MySQL-blue?style=for-the-badge&logo=mysql)](https://www.mysql.com/)
[![Language-PHP](https://img.shields.io/badge/Language-PHP-777BB4?style=for-the-badge&logo=php)](https://www.php.net/)
[![XAMPP-Ready](https://img.shields.io/badge/Stack-XAMPP-orange?style=for-the-badge&logo=xampp)](https://www.apachefriends.org/)

## 📖 Overview
The **Eco-Friendly Travel Planner** is a web-based application designed to help modern travelers minimize their environmental footprint. Unlike standard travel apps, this platform integrates **sustainability scoring**, carbon emission tracking, and localized eco-tips to ensure your journey respects both the planet and local communities.

Built as part of the **CMSC 207** curriculum, this project focuses on robust CRUD operations, secure database management, and user-centric design.

---

## ✨ Key Features
* **Smart Eco-Scoring:** A dynamic engine that calculates sustainability scores (1–100) and carbon footprints ($CO_2e$) by factoring in transport emission rates, traveler count, and a logarithmic distance penalty..
* **Destination Database:** Browse locations globally with specific "Eco-Notes" for responsible tourism.
* **Overlap Prevention Logic:** Intelligent scheduling that prevents users from booking conflicting itineraries.
* **Secure Authentication:** User registration and session-based login systems.
* **Personal Dashboard:** Manage, update, and cancel your upcoming green adventures.
* **Edit Profile:** Update your personal information including name, email, and password.

---

## 🧪 Sustainability Logic
The core of this application is a custom-built scoring engine that evaluates the environmental impact of every trip.

### 1. Carbon Footprint Calculation
The application calculates the total CO₂e (Carbon Dioxide Equivalent) based on the following formula:
$$Estimated Emission = Emission Factor \times Distance (km) \times Traveler Count$$

**Emission Factors used:**
* **Walking/Biking:** 0.00
* **Train:** 0.05 | **Public Bus:** 0.10
* **Ferry/Motorcycle:** 0.12
* **Private Car:** 0.21 | **Airplane:** 0.25

### 2. Sustainability Score (1-100)
The final score is determined by balancing the transport mode, distance, and the destination's eco-rating:
1. **Base Score:** Assigned by transport mode (e.g., Walking = 100, Airplane = 25).
2. **Distance Penalty:** A logarithmic penalty is applied so that longer trips impact the score more heavily:
   $$Penalty = 8 \times \log_{10}(Distance + 1)$$
3. **Eco-Bonus:** Destinations with high eco-ratings (🍃) grant a bonus:
   $$Bonus = Destination Eco Rating \times 3$$

**Final Calculation:**
`Score = Base Score - Distance Penalty + Eco Bonus`  
*(Clamped between a minimum of 5 and a maximum of 100)*

---

## 🛠️ Tech Stack
* **Backend:** PHP 8.2.12
* **Database:** MySQL (Relational Schema with Foreign Key Constraints)
* **Frontend:** Semantic HTML5, CSS3 (Custom Grid Layouts), JavaScript
* **Environment:** XAMPP (Apache Server)

---

## 🚀 Installation & Setup

### 1. Prerequisites
* Download and install [XAMPP](https://www.apachefriends.org/index.html).
* Ensure **Git** is installed on your system.

### 2. Clone the Repository
Open your terminal or command prompt, navigate to your XAMPP `htdocs` folder, and clone the project:
```bash
cd C:/xampp/htdocs
git clone [https://github.com/ajbalaga/Eco-friendly-travel-planner.git](https://github.com/ajbalaga/Eco-friendly-travel-planner.git)
```

### 3. Start XAMPP Services
1. Open the **XAMPP Control Panel** from your computer.
2. Click the **Start** button next to **Apache**.
3. Click the **Start** button next to **MySQL**.
   * *Ensure both modules turn green before moving to the next step.*

### 4. MySQL Database Setup
1. Open your web browser and go to: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
2. Click **New** in the left-hand sidebar.
3. Enter the Database name: `ecoFriendly_travel_planner_db` and click **Create**.
4. Once the database is selected, click the **Import** tab at the top of the page.
5. Click **Choose File** and navigate to your project directory:
   `C:/xampp/htdocs/Eco-friendly-travel-planner/database/ecoFriendly_travel_planner_db.sql`
6. Scroll to the bottom and click **Import** (or **Go**).

### 5. Access the Application
With the services running and the database ready, you can now launch the planner:
1. Open your web browser.
2. Enter the following URL:
> **[http://localhost/ecoFriendly_travel_planner/](http://localhost/ecoFriendly_travel_planner/)**

---

## 📂 Project Structure
* **`/assets`** — Contains `/css` (styling), `/images` (media), and `/js` (frontend scripts).
* **`/auth`** — Handles user authentication (`login.php`, `logout.php`, `register.php`).
* **`/config`** — Database connection settings (`database.php`).
* **`/database`** — Contains the essential `.sql` schema file for setup.
* **`/pages`** — Core application views like the dashboard and trip planning tools.
* **`index.php`** — The main landing page and entry point.

---

## 🛠️ Troubleshooting
* **Database Connection Error:** Verify the settings in `/config/database.php`. By default, XAMPP uses `root` as the user with no password.
* **Page Not Found:** Ensure the folder name in your `htdocs` directory matches the URL exactly.

**Happy Eco-Traveling!** 🌿
