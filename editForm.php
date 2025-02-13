<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['department'])) {
    header("Location: login.php");
    exit();
}

include 'dbconn.php';

// Get the filename from the parameter
$filename = isset($_GET['filename']) ? $_GET['filename'] : '';

// If the filename is invalid or empty, redirect back
if (empty($filename)) {
    header("Location: home.php");
    exit();
}

// Prepare the SQL statement to fetch the record based on the filename and username
$stmt = $connection->prepare("SELECT * FROM form WHERE filename=? AND department=?");
$stmt->bind_param("si", $filename, $_SESSION['department']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Check if the row exists
if ($result->num_rows === 0) {
    echo "No data found for this entry.";
    exit();
}

// If form is submitted, update the record
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and sanitize form data
    $category = htmlspecialchars($_POST['category']);
    $pic = htmlspecialchars($_POST['pic']);
    $service = htmlspecialchars($_POST['service']);
    $company = htmlspecialchars($_POST['company']);
    $start = htmlspecialchars($_POST['start']);
    $endDate = htmlspecialchars($_POST['endDate']);
    $sqft = htmlspecialchars($_POST['sqft']);
    $rent = htmlspecialchars($_POST['rent']);
    $file_names_string = htmlspecialchars($_POST['file_names_string']);
    $remarks = htmlspecialchars($_POST['remarks']);
    
    // Directory where the files will be uploaded
    $target_dir = "uploads/";
    
    // Handle file removal
    $existing_files = explode(',', $row['filename']);
    if (isset($_POST['remove_files']) && !empty($_POST['remove_files'])) {
        $files_to_remove = $_POST['remove_files'];
        
        // Remove files from the server and filter them out from `$existing_files`
        foreach ($files_to_remove as $file) {
            $file_path = $target_dir . basename($file);
            if (file_exists($file_path)) {
                unlink($file_path); // Remove the file from the server
            }
            // Filter out the removed file from the existing files array
            $existing_files = array_filter($existing_files, function($f) use ($file) {
                return $f !== $file;
            });
        }
    }

    $new_file_names = [];

    // Only handle file uploads if files are actually uploaded
    if (isset($_FILES["files"]) && count($_FILES["files"]["name"]) > 0 && $_FILES["files"]["name"][0] != "") {
        foreach ($_FILES["files"]["name"] as $key => $name) {
            $target_file = $target_dir . basename($name);
            $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Check the file type
            if (!in_array($file_type, ["jpg", "jpeg", "png", "gif", "pdf", "docx", "doc"])) {
                echo "Sorry, only JPG, JPEG, PNG, GIF, PDF, DOCX, and DOC files are allowed.";
                exit();
            }

            // Check the file size
            if ($_FILES["files"]["size"][$key] > 5000000) {
                echo "Sorry, your file is too large.";
                exit();
            }

            // Upload the file
            if (move_uploaded_file($_FILES["files"]["tmp_name"][$key], $target_file)) {
                $new_file_names[] = $name; // Add the new file name to the array
            } else {
                echo "Sorry, there was an error uploading your file.";
                exit();
            }
        }
    }

    // Combine updated existing files and new files, or use only existing files if no new uploads
    $all_file_names = !empty($new_file_names) ? array_merge($existing_files, $new_file_names) : $existing_files;
    $file_names_string = implode(',', $all_file_names); // Join file names with a comma


    // Calculate months left from the end date
    $endDateTimestamp = strtotime($endDate);
    $currentTimestamp = time();

    // Calculate the difference in months
    $monthsLeft = 0;
    $loopTimestamp = $currentTimestamp;
    while ($loopTimestamp < $endDateTimestamp) {
        $loopTimestamp = strtotime('+1 month', $loopTimestamp);
        $monthsLeft++;
    }

    // If the end date is in the past, calculate the difference in the opposite direction
    if ($currentTimestamp > $endDateTimestamp) {
        $monthsLeft = -$monthsLeft;
        $loopTimestamp = $endDateTimestamp;
        while ($loopTimestamp < $currentTimestamp) {
            $loopTimestamp = strtotime('+1 month', $loopTimestamp);
            $monthsLeft--;
        }
    }

    // Alternatively, you can use a simpler approach using date_diff
    $endDateDate = new DateTime($endDate);
    $currentDate = new DateTime();
    $interval = $endDateDate->diff($currentDate);
    $monthsLeft = $interval->m + ($interval->y * 12);
    if ($endDateDate < $currentDate) {
        $monthsLeft = -$monthsLeft;
    }

    // Update the database
   $stmt = $connection->prepare("UPDATE form SET category=?, pic=?, service=?, company=?, start=?, endDate=?, sqft=?, rent=?, filename=?, remarks=?, monthsLeft=? WHERE filename=? AND department=?");
    $stmt->bind_param("ssssssssssisi", $category, $pic, $service, $company, $start, $endDate, $sqft, $rent, $file_names_string, $remarks, $monthsLeft, $filename, $_SESSION['department']);
    var_dump($category, $pic, $service, $company, $start, $endDate, $sqft, $rent, $file_names_string, $remarks, $monthsLeft, $filename, $_SESSION['department']);

    if ($stmt->execute()) {
        echo "Record updated successfully!";
        header("Location: view.php?filename=" . $file_names_string);
        exit();
    } else {
        echo "Error updating record: " . $stmt->error;
    }

    $stmt->close();
    $connection->close();

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <link rel="shortcut icon" type="x-icon" href="hsptl.png">
    <title>Update Record</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f7fafc;
            color: #333;
            margin: 0;
        }
        .container {
            max-width: 600px;
            margin: 15px auto;
            padding: 30px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            font-size: 28px;
            color: #444;
            margin-bottom: 20px;
        }
        form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        label {
            font-weight: 600;
            margin-bottom: -30px;
            margin-left: 50px;
            color: #555;
        }
        input, textarea, select {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 80%;
            font-size: 16px;
            background-color: #f9f9f9;
        }
        input[type="date"], input[type="number"] {
            width: 80%;
        }
        textarea {
            height: 80px;
            resize: none;
            width: 80%;
        }
        .button {
            background-color: blue;
            color: white;
            border-radius: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            padding: 10px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn {
            background-color: blue;
            color: white;
            padding: 5px 20px;
            border-radius: 15px;
            cursor: pointer;
            text-align: center;
            font-size: 16px;
            transition: background 0.3s ease;
            grid-column: span 2;
        }
        .btn:hover {
            background: #fff;
            color: grey;
        }

        .back-button {
            background-color: transparent;
            border: none;
            color: black;
            font-size: 40px;
            cursor: pointer; 
            transition: color 0.3s ease, transform 0.5s ease;
        }
        .back-button:hover{
          color: rgb(208, 208, 208, 1.0);
          transform: scale(1.4);
        }
    </style>
</head>
<body>

<div class="container">
    <a onclick="history.back()" class="back-button" type="button"><i class='bx bx-arrow-back'></i></a>
    <h1>Update Record</h1>
    <form method="POST" enctype="multipart/form-data">
        <label for="category">Category:</label>
        <select name="category" id="category" required>
            <option value="licensing" <?php echo ($row['category'] == 'licensing') ? 'selected' : ''; ?>>Licensing</option>
            <option value="tenant" <?php echo ($row['category'] == 'tenant') ? 'selected' : ''; ?>>Tenant</option>
            <option value="service" <?php echo ($row['category'] == 'service') ? 'selected' : ''; ?>>Service</option>
            <option value="outsource" <?php echo ($row['category'] == 'outsource') ? 'selected' : ''; ?>>Outsource</option>
            <option value="biomedical-facilities" <?php echo ($row['category'] == 'biomedical-facilities') ? 'selected' : ''; ?>>Marcomm</option>
            <option value="tenant" <?php echo ($row['category'] == 'marcomm') ? 'selected' : ''; ?>>Marcomm</option>
            <option value="clinical" <?php echo ($row['category'] == 'clinical') ? 'selected' : ''; ?>>Clinical</option>
            <option value="support" <?php echo ($row['category'] == 'support') ? 'selected' : ''; ?>>Support</option>
        </select>

        <label for="pic">PIC:</label>
        <input type="text" name="pic" id="pic" value="<?php echo $row['pic']; ?>" >

        <label for="service">Service:</label>
        <input type="text" name="service" id="service" value="<?php echo $row['service']; ?>" >

        <label for="company">Company:</label>
        <input type="text" name="company" id="company" value="<?php echo $row['company']; ?>" >

        <label for="start">Start Date:</label>
        <input type="date" name="start" id="start" value="<?php echo $row['start']; ?>" >

        <label for="endDate">End Date:</label>
        <input type="date" name="endDate" id="endDate" value="<?php echo $row['endDate']; ?>" >

        <label for="sqft">SQFT:</label>
        <input type="text" name="sqft" id="sqft" value="<?php echo $row['sqft']; ?>" >

        <label for="rent">Rent:</label>
        <input type="text" name="rent" id="rent" value="<?php echo $row['rent']; ?>" >

        <label for="remarks">Remarks:</label>
        <textarea name="remarks" id="remarks" ><?php echo $row['remarks']; ?></textarea>

        <label for="files">Upload Files:</label>
        <input type="file" name="files[]" id="files" value="<?php echo htmlspecialchars($row['file']); ?>" multiple>

        <h3>Existing Files:</h3>
        <ul>
            <?php
            $file_path = ''; // Initialize to avoid undefined variable notice
            if (isset($_POST['remove_files']) && !empty($_POST['remove_files'])) {
                $files_to_remove = $_POST['remove_files'];
                foreach ($files_to_remove as $file) {
                    $file_path = $target_dir . basename($file);
                    if (file_exists($file_path)) {
                        unlink($file_path); // Remove the file from the server
                    }
                }
            }

            if (isset($_POST['remove_files']) && !empty($_POST['remove_files'])) {
                $files_to_remove = $_POST['remove_files'];
                foreach ($files_to_remove as $file) {
                    $file_path = $target_dir . basename($file);
                    if (file_exists($file_path)) {
                        unlink($file_path); // Remove the file from the server
                    } else {
                        echo "File does not exist: $file_path<br>"; // Debugging output
                    }
                }
            } else {
                echo "No files to remove.<br>"; // Debugging output
            }


            $existing_files = explode(',', $row['filename']);
            foreach ($existing_files as $file) {
                echo "<li>$file <input type='checkbox' name='remove_files[]' value='" . htmlspecialchars($file) . "'> Remove</li>";
            }
            ?>
        </ul>

        <input type="hidden" name="file_names_string" value="<?php echo $row['filename']; ?>">
        <button type="submit" class="btn">Update</button>
    </form>
</div>

</body>
</html>
