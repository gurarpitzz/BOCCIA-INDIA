# Boccia India - Official Web Portal & CMS (BSFI)

[![Affiliated: MYAS](https://img.shields.io/badge/Affiliation-MYAS--India-green?style=flat-square)](https://yas.nic.in/)
[![Affiliated: PCI](https://img.shields.io/badge/Affiliation-PCI-blue?style=flat-square)](https://www.paralympicindia.org.in/)
[![Affiliated: World Boccia](https://img.shields.io/badge/Affiliation-World_Boccia-orange?style=flat-square)](https://worldboccia.com/)

An enterprise-grade sports management system and public portal custom-developed for the **Boccia Sports Federation of India (BSFI)**. BSFI is the national governing body for the sport of Boccia in India, recognized by the **Ministry of Youth Affairs and Sports (MYAS)**, and officially affiliated with the **Paralympic Committee of India (PCI)** and **World Boccia**.

> [!IMPORTANT]
> **PROPRIETARY & COMMERCIAL LICENSE**
> This repository contains a fully custom, paid project commissioned by **BOCCIA INDIA (Boccia Sports Federation of India)**. All code, design assets, and database schemas are proprietary. Unauthorized copying, distribution, or modifications of this project are strictly prohibited.

---

## 📸 Website Highlights

### 1. Public Gallery & Album Viewer
A premium, responsive album grid featuring category filters and horizontal swipe navigation on mobile viewports. Clicking an album transitions into a beautiful masonry layout with an integrated media lightbox.
![Public Gallery](docs/screenshots/gallery_homepage.png)

### 2. Admin Control Desk Dashboard
A centralized system dashboard showing real-time statistics (total registrations, classifications, missing athlete metadata), timeline logs, and quick actions.
![Admin Dashboard](docs/screenshots/admin_dashboard.png)

---

## 🌟 Key Features

### 💻 Public Web Portal
*   **Accessible Design System**: Tailored HSL color palette and accessibility controls (contrast toggle, text magnifier, NADA/WADA compliant layouts).
*   **Dynamic Interactive Map**: SVG-based state map reflecting live athlete registration density across India.
*   **Media Center Archive**: Structured **Category ➔ Album ➔ Photo** organization matching professional athletic federations.
*   **Online Registration**: Custom forms for Athletes and Officials with data sanitization, profile completeness scoring, and security checks.

### 🛡️ Administrative CMS (Control Desk)
*   **Metrics Dashboard**: Live KPI cards detailing missing files, status of registries, and database health.
*   **Role-Based Security**: Restrictive authentication layers separating Administrators, Editors, and Viewers.
*   **Bulk Sync & Data Import**: Deduplicated folder scanning, EXIF stripping, and Excel/CSV parsing helpers.
*   **Audit Trail & Safeguards**: Comprehensive log tracking of staff activity and database backups.

---

## 🏛️ Legal & Compliance Structure

### 1. Affiliation & Trademark Notice
The names, logos, and symbols of **BOCCIA INDIA**, **BSFI**, **MYAS**, **PCI**, and **World Boccia** are protected trademarks. This software is built in accordance with the official guidelines of:
- **Ministry of Youth Affairs & Sports (MYAS)**, Government of India.
- **Paralympic Committee of India (PCI)**.
- **World Boccia (International Boccia Sports Federation)**.

### 2. Anti-Doping Regulations
The portal hosts official documentation and links regarding anti-doping policies in compliance with the **National Anti-Doping Agency (NADA)** and **World Anti-Doping Agency (WADA)** rules to promote clean sport.

### 3. Data Protection & Privacy
All athlete registry details, classifications (BC1, BC2, BC3, BC4, BC5), and documents are stored securely. Personal identifiable information (PII) is encrypted and accessed exclusively by authorized executive officers.

---

## 🚀 Setup & Execution

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Web server (Apache, Nginx, or XAMPP suite)

### Installation
1.  Clone the repository into your server root directory.
2.  Import the database structure from the `schema.sql` file:
    ```sql
    mysql -u [user] -p [database_name] < schema.sql
    ```
3.  Configure your credentials in `includes/db.php`:
    ```php
    $host = 'localhost';
    $db   = 'your_database';
    $user = 'your_username';
    $pass = 'your_password';
    ```
4.  Launch your local PHP development server or run XAMPP.

---

## 📄 License
Copyright © 2026 **Boccia Sports Federation of India (BSFI)**. All rights reserved. 
Licensed exclusively for deployment on the official Boccia India domains.
