Pickup Basketball Stat Tracker App - CIS 4004 Project #3
This is a LAMP-stack web application for tracking pickup basketball games and player statistics.
Configuration & Setup
1.  Database Setup:
Open phpMyAdmin (http://localhost/phpmyadmin). 
Create a new database named “pickuphoops”.
Import the schema.sql file included in this folder to create the necessary tables (Users, Players, Games, Game_Stats).
2.  Database Connection:
This application uses a .env file for configuration.
Open the .env file (created to separate the API key).
Update the DB_HOST, DB_NAME, DB_USER, and DB_PASS values to match your local MySQL configuration.
Default XAMPP settings are usually:
DB_HOST=127.0.0.1
DB_NAME=pickuphoops
DB_USER=root
DB_PASS= (leave empty)
Note: The db_connect.php should load these values automatically.


This app also has an added feature that uses a Google Gemini API key to generate a “Player Roaster” based on stats. This is also routed through the .env file. This isn’t required for the basis of the app to work, but if you would like to grade with this feature, you can acquire an API key following these steps:
Navigate to https://aistudio.google.com/app/api-keys 
Sign in with your Google account
Click "Create API key". 
Choose a Google Cloud project or create a new one to organize your keys. 
Copy the API key and paste it into the .env file
3.  Running the Application:
Place this entire folder in your web server's document root (e.g., C:\xampp\htdocs\CIS4004-PickupBasketballApp).
Open your web browser and navigate to http://localhost/CIS4004-PickupBasketballApp/index.php. 
Testing Steps
1.  Registration & Login:
 On the login page (http://localhost/CIS4004-PickupBasketballApp/index.php), enter a new username and password.
Click "Register" to create a new Standard User account.
Log in with your new credentials.
Note: To test Admin features, you will need to manually update your user role to Admin in the Users table under the Role column using phpMyAdmin, as new users are Standard by default.
2.  Admin Features:
Manage Players: Go to "Manage Players". Add a new player with a name, team, and nickname. Upload a photo if desired. You can also Edit or Delete existing players.
Record Game: Go to "Record New Game". Enter the date, location, and score. Select players from the dropdown to add them to the game. Enter their stats (Points, Rebounds, etc.). Click "Record Game".
Manage Games: Go to "Manage Games". You will see a list of all recorded games. Click "Edit" to modify a game's details or stats, or "Delete" to remove it.
3.  Standard User Features:
View Stats: Go to "View Player Stats". You will see a leaderboard of all players with their aggregated statistics (PPG, RPG, FG%, etc.).
Filter: Use the dropdown to filter stats by Season (Year).
Player Profile: Click on a player's name to view their individual profile and game logs.


