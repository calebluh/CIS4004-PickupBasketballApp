***

# Pickup Basketball Stat Tracker App

This is a **LAMP stack web application** designed to track pickup basketball games and player statistics. It provides a platform for users to manage player profiles, record game results, and view aggregated player statistics and leaderboards.

## Features

* **User Authentication:** Standard and Admin roles with registration and login.
* **Player Management (Admin):** Add, edit, and delete players, including details like name, team, nickname, and photo.
* **Game Recording (Admin):** Log new games with date, location, score, and individual player statistics.
* **Game Management (Admin):** View, edit, and delete recorded games.
* **Player Stats Leaderboard (Standard/Admin):** View aggregated player statistics (PPG, RPG, FG%, etc.).
* **Stats Filtering:** Filter leaderboards by season (year).
* **Player Profiles:** Detailed view of individual player stats and game logs.
* **Generative AI Feature (Optional):** Integration with the Google Gemini API to generate a "Player Roster" based on stats.

---

## ⚙️ Configuration & Setup

This application requires a **LAMP** stack, such as **XAMPP** or **WAMP**.

### 1. Database Setup

1.  Open **phpMyAdmin** in your web browser (usually at `http://localhost/phpmyadmin`).
2.  Create a new database named **`pickuphoops`**.
3.  **Import** the provided `schema.sql` file into the new `pickuphoops` database to create the necessary tables: `Users`, `Players`, `Games`, and `Game_Stats`.
    

### 2. Database Connection

The application uses an environment file (`.env`) for configuration.

1.  Open the **`.env`** file located in the root directory.
2.  Update the following values to match your local MySQL configuration:

| Variable | Default XAMPP Value | Description |
| :--- | :--- | :--- |
| **DB\_HOST** | `127.0.0.1` | Your MySQL server host (usually localhost) |
| **DB\_NAME** | `pickuphoops` | The database name you created |
| **DB\_USER** | `root` | Your MySQL username |
| **DB\_PASS** | (leave empty) | Your MySQL password |

> **Note:** The `db_connect.php` file automatically loads these settings.

### 3. Google Gemini API Integration (Optional)

To enable the "Player Roaster" generative feature, you will need a Google Gemini API key.

1.  Navigate to **[https://aistudio.google.com/app/api-keys](https://aistudio.google.com/app/api-keys)**.
2.  Sign in with your Google account and click "**Create API key**".
3.  Copy the generated key.
4.  Paste the API key into the **`GEMINI_API_KEY`** variable in your **`.env`** file.

### 4. Running the Application

1.  Place this entire project folder (e.g., `CIS4004-PickupBasketballApp`) into your web server's document root (e.g., `C:\xampp\htdocs\`).
2.  Open your web browser and navigate to the application's home page:

    `http://localhost/CIS4004-PickupBasketballApp/index.php`

---

## Testing Steps

### 1. Registration & Login

1.  On the login page (`index.php`), enter a new **username** and **password**.
2.  Click "**Register**" to create a new **Standard User** account.
3.  Log in with your new credentials.

> **Admin Access:** New users are **Standard** by default. To test Admin features, manually update your user role to **`Admin`** in the `Role` column of the `Users` table using phpMyAdmin.

### 2. Admin Features (Role: `Admin`)

| Feature | Steps to Test |
| :--- | :--- |
| **Manage Players** | Go to **"Manage Players"**. Add a new player with name, team, and nickname. Optionally, upload a photo. Test the **Edit** and **Delete** functionality on existing players. |
| **Record New Game** | Go to **"Record New Game"**. Enter the date, location, and final score. Use the dropdown to select players and enter their stats (Points, Rebounds, Assists, etc.). Click **"Record Game"**. |
| **Manage Games** | Go to **"Manage Games"**. Find the game you recorded and click **"Edit"** to modify its details or player stats. Test the **"Delete"** function to remove a game. |

### 3. Standard User Features (Role: `Standard`)

1.  **View Player Stats:** Go to **"View Player Stats"**. Verify that the page displays a **leaderboard** of all players with their calculated aggregated statistics (e.g., PPG, FG%).
2.  **Filter:** Use the **dropdown** to filter the displayed stats by **Season (Year)**.
3.  **Player Profile:** Click on a **player's name** to view their individual profile, detailed season stats, and a log of all games they played in.

***
