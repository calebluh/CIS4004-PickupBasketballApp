<!-- 
    Caleb Luh 
    11/20/2025
    Pickup Basketball Stat Tracker App
    CIS 4004 - LAMP-Stack Web Application Project 3
-->

<?php 

session_start(); 
include 'db_connect.php'; 
 
// Only admins can see this page
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') { 
    header('Location: index.php');     
    exit; 
} 
 
$message = ''; 
$is_error = false; 
$edit_player = null; 

// Function to save uploaded images
function handleFileUpload($file) {
    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = uniqid('player_', true) . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                return $destPath;
            }
        }
    }
    return null;
}
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {     
    if (isset($_POST['add_player'])) {         
        $name = trim($_POST['name']); 
        $player_number = !empty($_POST['player_number']) ? (int)$_POST['player_number'] : null;
        $team = trim($_POST['team']);
        $nickname = trim($_POST['nickname']);
        $height = trim($_POST['height']);
        $weight = trim($_POST['weight']);
        $hometown = trim($_POST['hometown']);
        $birthdate = !empty($_POST['birthdate']) ? $_POST['birthdate'] : null;
        $image_path = handleFileUpload($_FILES['player_image']);
         
        if (!empty($name)) { 
            $stmt = $pdo->prepare("INSERT INTO Players (name, player_number, team, nickname, height, weight, hometown, birthdate, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"); 
            $stmt->execute([$name, $player_number, $team, $nickname, $height, $weight, $hometown, $birthdate, $image_path]);             
            $message = "Player '{$name}' added successfully."; 
        } else { 
            $message = "Player name cannot be empty."; 
            $is_error = true; 
        } 
    }  
    elseif (isset($_POST['update_player'])) { 
        $player_id = $_POST['player_id']; 
        $name = trim($_POST['name']); 
        $player_number = !empty($_POST['player_number']) ? (int)$_POST['player_number'] : null;
        $team = trim($_POST['team']);
        $nickname = trim($_POST['nickname']);
        $height = trim($_POST['height']);
        $weight = trim($_POST['weight']);
        $hometown = trim($_POST['hometown']);
        $birthdate = !empty($_POST['birthdate']) ? $_POST['birthdate'] : null;
        
        // Update the player's picture if they uploaded a new one
        $image_path = null;
        if (isset($_FILES['player_image']) && $_FILES['player_image']['error'] === UPLOAD_ERR_OK) {
            $image_path = handleFileUpload($_FILES['player_image']);
            // Update with new image
            $stmt = $pdo->prepare("UPDATE Players SET name = ?, player_number = ?, team = ?, nickname = ?, height = ?, weight = ?, hometown = ?, birthdate = ?, image_path = ? WHERE player_id = ?"); 
            $stmt->execute([$name, $player_number, $team, $nickname, $height, $weight, $hometown, $birthdate, $image_path, $player_id]);
        } else {
            // Keep existing image
            $stmt = $pdo->prepare("UPDATE Players SET name = ?, player_number = ?, team = ?, nickname = ?, height = ?, weight = ?, hometown = ?, birthdate = ? WHERE player_id = ?"); 
            $stmt->execute([$name, $player_number, $team, $nickname, $height, $weight, $hometown, $birthdate, $player_id]);
        }
 
        $message = "Player #{$player_id} updated successfully."; 
    } 
    elseif (isset($_POST['delete_player'])) { 
        $player_id = $_POST['player_id']; 
        $stmt = $pdo->prepare("SELECT image_path FROM Players WHERE player_id = ?");
        $stmt->execute([$player_id]);
        $player = $stmt->fetch();
        if ($player && $player['image_path'] && file_exists($player['image_path'])) {
            unlink($player['image_path']);
        }

        $stmt = $pdo->prepare("DELETE FROM Players WHERE player_id = ?"); 
        $stmt->execute([$player_id]); 
        $message = "Player deleted successfully."; 
    } 
} 
 
if (isset($_GET['edit_id'])) { 
    $stmt = $pdo->prepare("SELECT * FROM Players WHERE player_id = ?"); 
    $stmt->execute([$_GET['edit_id']]);     
    $edit_player = $stmt->fetch(); 
} 
 
$players = $pdo->query("SELECT * FROM Players ORDER BY name ASC")->fetchAll(); 
?> 
 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Player Management</title>     
    <link rel="stylesheet" href="style.css"> 
    <style>
        .player-img-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ddd;
        }
    </style>
</head> 
<body> 
    <div class="container"> 
        <h1>Player Management</h1> 
        <div class="nav-links"> 
            <a href="dashboard.php">← Dashboard</a> 
            <a href="game_entry.php">Record Game</a> 
            <a href="games_admin.php">Manage Games</a>
            <a href="stats_view.php">View Stats</a>             
            <a href="logout.php">Logout</a> 
        </div> 
 
        <?php if ($message): ?> 
            <div class="message <?php echo $is_error ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($message); ?></div> 
        <?php endif; ?> 
 
        <!-- Player ADD/UPDATE Form --> 
        <h2><?php echo $edit_player ? 'Edit Player' : 'Add New Player'; ?></h2> 
        <form method="POST" enctype="multipart/form-data"> 
            <?php if ($edit_player): ?> 
                <input type="hidden" name="update_player" value="1"> 
                <input type="hidden" name="player_id" value="<?php echo htmlspecialchars($edit_player['player_id']); ?>"> 
            <?php else: ?> 
                <input type="hidden" name="add_player" value="1"> 
            <?php endif; ?> 
 
            <div style="display: flex; gap: 20px;">
                <div style="flex: 1;">
                    <label for="name">Name:</label> 
                    <input type="text" id="name" name="name" required placeholder="Player Name" value="<?php echo $edit_player ? htmlspecialchars($edit_player['name']) : ''; ?>"> 
                </div>
                <div style="width: 150px;">
                    <label for="player_number">Jersey #:</label>
                    <input type="number" id="player_number" name="player_number" placeholder="#" value="<?php echo $edit_player ? htmlspecialchars($edit_player['player_number'] ?? '') : ''; ?>">
                </div>
            </div>

            <div style="display: flex; gap: 20px;">
                <div style="flex: 1;">
                    <label for="team">Team:</label> 
                    <input type="text" id="team" name="team" placeholder="Team Name" value="<?php echo $edit_player ? htmlspecialchars($edit_player['team'] ?? '') : ''; ?>"> 
                </div>
                <div style="flex: 1;">
                    <label for="nickname">Nickname:</label>
                    <input type="text" id="nickname" name="nickname" placeholder="Nickname" value="<?php echo $edit_player ? htmlspecialchars($edit_player['nickname'] ?? '') : ''; ?>">
                </div>
            </div>

            <label for="player_image">Player Image:</label>
            <?php if ($edit_player && !empty($edit_player['image_path'])): ?>
                <div style="margin-bottom: 10px;">
                    <img src="<?php echo htmlspecialchars($edit_player['image_path']); ?>" alt="Current Image" style="height: 100px; border-radius: 8px;">
                    <p style="font-size: 0.8em; color: #666;">Current Image</p>
                </div>
            <?php endif; ?>
            <input type="file" id="player_image" name="player_image" accept="image/*">
             
            <fieldset style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                <legend>Biometrics</legend>
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 120px;">
                        <label for="height">Height:</label>
                        <input type="text" id="height" name="height" placeholder="e.g. 6'2&quot;" value="<?php echo $edit_player ? htmlspecialchars($edit_player['height'] ?? '') : ''; ?>">
                    </div>
                    <div style="flex: 1; min-width: 120px;">
                        <label for="weight">Weight (lbs):</label>
                        <input type="text" id="weight" name="weight" placeholder="e.g. 185" value="<?php echo $edit_player ? htmlspecialchars($edit_player['weight'] ?? '') : ''; ?>">
                    </div>
                    <div style="flex: 1; min-width: 150px;">
                        <label for="birthdate">Birthdate:</label>
                        <input type="date" id="birthdate" name="birthdate" value="<?php echo $edit_player ? htmlspecialchars($edit_player['birthdate'] ?? '') : ''; ?>">
                    </div>
                </div>
                <div style="margin-top: 10px;">
                    <label for="hometown">Hometown:</label>
                    <input type="text" id="hometown" name="hometown" placeholder="City, State" value="<?php echo $edit_player ? htmlspecialchars($edit_player['hometown'] ?? '') : ''; ?>">
                </div>
            </fieldset>
            
            <button type="submit" class="<?php echo $edit_player ? 'btn' : 'btn-success'; ?>"> 
                <?php echo $edit_player ? 'Save Changes' : 'Add Player'; ?> 
            </button> 
            <?php if ($edit_player): ?> 
                <a href="players_admin.php" class="btn">Cancel Edit</a> 
            <?php endif; ?> 
        </form> 
 
        <hr> 
 
        <!-- Player READ Table --> 
        <h2>Current Roster (<?php echo count($players); ?> Players)</h2> 
        <table> 
            <thead> 
                <tr> 
                    <th>Image</th>
                    <th>#</th>
                    <th>Name</th> 
                    <th>Details</th> 
                    <th>Actions</th> 
                </tr> 
            </thead> 
            <tbody> 
                <?php foreach ($players as $player): ?>                 
                <tr> 
                    <td>
                        <?php if (!empty($player['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($player['image_path']); ?>" alt="Img" class="player-img-thumb">
                        <?php else: ?>
                            <div class="player-img-thumb" style="background: #eee; display: flex; align-items: center; justify-content: center; color: #aaa;">?</div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($player['player_number'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($player['name']); ?></td> 
                    <td>
                        <small>
                            <?php 
                            $details = [];
                            if (!empty($player['team'])) $details[] = 'Team: ' . $player['team'];
                            if (!empty($player['nickname'])) $details[] = '"' . $player['nickname'] . '"';
                            if (!empty($player['height'])) $details[] = $player['height'];
                            if (!empty($player['weight'])) $details[] = $player['weight'] . ' lbs';
                            if (!empty($player['hometown'])) $details[] = $player['hometown'];
                            echo implode(' | ', $details);
                            ?>
                        </small>
                    </td> 
                    <td> 
                        <a href="players_admin.php?edit_id=<?php echo $player['player_id']; ?>" class="btn">Edit</a> 
                        <!-- Delete Form --> 
                        <form method="POST" style="display: inline-block; background: none; border: none; padding: 0; margin: 0;"> 
                            <input type="hidden" name="player_id" value="<?php echo $player['player_id']; ?>"> 
                            <button type="submit" name="delete_player" class="btn-danger" onclick="return confirm('WARNING: Deleting a player removes all their game stats. Continue?');">Delete</button> 
                        </form> 
                    </td> 
                </tr> 
                <?php endforeach; ?> 
            </tbody> 
        </table> 
    </div> 
</body> 
</html> 
