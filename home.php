<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['department'])) {
    header("Location: login.php");
    exit(); // Stop further script execution
}

// Get the user_id from the session
$department = $_SESSION['department'];

include 'dbconn.php'; // Include database connection

// Define categories
$categories = ["licensing", "tenant", "services", "outsource", "biomedical-facilities", "marcomm", "clinical", "support"];

// Function to execute SQL query and return result
function executeQuery($query) {
    global $connection;
    $result = mysqli_query($connection, $query);
    if (!$result) {
        die("Error in query: " . mysqli_error($connection));
    }
    return $result;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="shortcut icon" type="x-icon" href="hsptl.png">

    <style>
         /* CSS styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-size: cover;
            background-position: center;
            color: white;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 80px;
            height: 100%;
            background-color: blue;
            backdrop-filter: blur(50px);
            border-right: 2px solid rgba(225, 225, 255, 0.2);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            padding: 6px 14px;
            transition: width 0.5s ease;
            z-index: 10;
        }

        .sidebar.active {
            width: 260px;
        }

        .sidebar .logo-menu {
            display: flex;
            align-items: center;
            width: 100%;
            height: 70px;
        }

        .sidebar .logo-menu .menu {
            font-size: 25px;
            color: black;
            pointer-events: none;
            opacity: 0;
            transition: 0.3s;
        }

        .sidebar.active .logo-menu .menu {
            opacity: 1;
            transition-delay: 0.2s;
        }

        .sidebar .logo-menu .toggle-btn {
            position: absolute;
            top: 20px; /* Adjusted top for better alignment */
            left: 25%; /* Adjusts correctly when sidebar is active */
            width: 40px;
            height: 40px;
            font-size: 22px;
            color: white;
            text-align: center;
            line-height: 40px;
            cursor: pointer;
            transition: 0.5s;
            z-index: 100; /* Ensure it stays on top */
        }

        .sidebar.active .logo-menu .toggle-btn {
            left: 80%; /* Adjusted for the active sidebar */
        }

        .sidebar .list {
            margin-top: 10px;
        }

        .sidebar .list .list-item {
            list-style: none;
            width: 100%;
            height: 50px;
            margin: 5px 0;
            line-height: 50px;
        }

        .sidebar .list .list-item a {
            display: flex;
            align-items: center;
            font-size: 18px;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.5s;
        }

        .sidebar .list .list-item.active a,
        .sidebar .list .list-item a:hover {
            background: rgba(255, 255, 255, 0.6);
        }

        .sidebar .list .list-item a i {
            min-width: 50px;
            height: 50px;
            text-align: center;
            line-height: 50px;
        }

        .sidebar .link-name {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }

        .sidebar.active .link-name {
            opacity: 1;
            pointer-events: auto;
            transition-delay: calc(0.1s * var(--i));
        }

        .container {
            padding-top: 40px;
            width: 100%;
            margin: 0 auto;
            flex: 1;
        }

        /* Table Styles */
        .center-table {
            display: flex;
            justify-content: center;
            background-color: rgba(240, 240, 240, 0.5);
            color: black;
            margin-top: 30px;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            overflow-x: visible;
        }

        .container {
            padding-top: 40px;
            width: 85%;
            margin: 0 auto;
            flex: 1;
        }

        #example_wrapper {
            width: 100%;
            overflow-x: visible;
        }

        #example {
            width: 100%;
            padding-top: 10px;
            color: black;
            table-layout: fixed;
        }

        #example th,
        #example td {
            border: 1px solid #ddd;
            text-align: center;
            overflow-wrap: break-word;
            word-wrap: break-word;
            padding: 12px;
            font-size: 14px;
        }

        .lower3months {
            background-color: #FDFF90;
        }
        .higher3months {
            background-color: #9EFBA8;
        }
        .over3months {
            background-color: #FF9A9A;
        }

        /* Button Styles */
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
            background: blue;
            color: white;
            font-size: 1.2em;
            padding: 5px 20px;
            text-decoration: none;
            border-radius: 20px;
        }

        .btn:hover {
            background: #fff;
            color: grey;
        }

        /* Misc Styles */
        h1 {
            color: black;
            text-shadow: 0 0 5px #999;
            font-size: 50px;
            text-align: center;
        }

        .dataTables_filter input[type="search"] {
            background-color: white;
            color: black;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 5px;
            box-shadow: none;
            max-width: 150px;
        }

        .dataTables_filter input[type="search"]:focus {
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }

        .filter-container {
            display: flex;
            justify-content: flex-end ;
        }

        .dataTables_filter {
            display: flex;
            align-items: center;
        }

        #categoryFilter {
            margin-left: 10px;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: white;
            color: black;
        }

        .wrapper {
            display: flex;
            margin: 15px auto;
            flex-direction: column;
            align-items: center;
            width: 85%;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.9);
        }

        .navbar {
            background: white;
            padding: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navbar .navdiv {
            display: flex;
            justify-content: space-around;
            align-items: center;
        }

        .navbar .navdiv h2 {
            font-size: 40px;
            font-weight: 600;
            color: black;
            margin: 0;
        }

        .navbar .navdiv .breadcrumb {
            display: flex;
            align-items: center;
            list-style: none;
            padding: 0;
        }

        .navbar .navdiv .breadcrumb li {
            margin: 0 10px;
            color: grey;
        }

        .navbar .navdiv .breadcrumb li a {
            text-decoration: none;
            color: grey;
        }

        .navbar .navdiv .breadcrumb li a.active {
            color: blue;
        }

    </style>
    <script>

        $(document).ready(function () {
            var table = $('#example').DataTable();

            // Filter table based on category selection
            $('#categoryFilter').on('change', function () {
                var selectedCategory = $(this).val();
                table.column(0).search(selectedCategory).draw();
            });

            // Toggle sidebar
            $('.toggle-btn').click(function () {
                $('.sidebar').toggleClass('active');
            });
        });

        document.addEventListener("DOMContentLoaded", function() {
        const table = document.querySelector(".center-table tbody");
        const rows = table.querySelectorAll("tr");

        // Assuming the table is rectangular (each row has the same number of cells)
        const columnCount = rows[0].cells.length;

        for (let colIndex = 0; colIndex < columnCount; colIndex++) {
            for (let rowIndex = 0; rowIndex < rows.length; rowIndex++) {
                const cell = rows[rowIndex].cells[colIndex]; // Get the cell at the specific column index

                if (colIndex === 8) { // Adjust this index if the target column position changes
                    const monthsLeft = parseInt(cell.textContent.trim(), 10); // Ensure trimming whitespace

                    if (!isNaN(monthsLeft)) { // Check if the value is a valid number
                        if (monthsLeft < 3 && monthsLeft >= 0) {
                            cell.classList.add("lower3months");
                        } else if (monthsLeft >= 3) {
                            cell.classList.add("higher3months");
                        } else {
                            cell.classList.add("over3months");
                        }
                    } else {
                        console.warn("Invalid monthsLeft value in cell:", cell.textContent);
                    }
                }
            }
        }
    });
    </script>
</head>
<body>

    <nav class="navbar">
        <div class="navdiv">
            <h2>Department : <?php echo htmlspecialchars($department); ?></h2>
        </div>
    </nav>
    <div class="wrapper">
    <div class="sidebar">
        <div class="logo-menu">
            <h2 class="menu" style="color: white;" >Menu</h2>
            <i class='bx bx-menu toggle-btn'></i>
        </div>
        <ul class="list">
            <li class="list-item active">
                <a href="">
                    <i class='bx bx-home'></i>
                    <span class="link-name" style="--i:1;">Dashboard</span>
                </a>
            </li>
            <li class="list-item">
                <a href="dashboard.php">
                    <i class='bx bx-file'></i>
                    <span class="link-name" style="--i:2;">Others</span>
                </a>
            </li>
            <li class="list-item">
                <a href="logout.php">
                    <i class='bx bx-log-out'></i>
                    <span class="link-name" style="--i:3;">Logout</span>
                </a>
            </li>
        </ul>
    </div>
   
    <div>
        <div class="filter-container">
            <label for="categoryFilter" style="color: black;">Category:</label>
            <select id="categoryFilter">
                <option value="">All</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <a href="form.php" class="btn" type="button">+ Add New</a>

        <div class="center-table">
            <table id="example" class="table table-striped">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>PIC</th>
                        <th>Service</th>
                        <th>Company/Act</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Rental</th>
                        <th>Remarks</th>
                        <th>Months Left Before End</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    // Prepare the SQL statement
                    $stmt = $connection->prepare("SELECT category, pic, service, company, start, endDate, rent, remarks, monthsLeft, filename, department FROM form WHERE department = ?");
                    if (!$stmt) {
                        die("Prepare failed: " . $connection->error);
                    }

                    // Bind parameters
                    if (!$stmt->bind_param("s", $department)) {
                        die("Bind failed: " . $stmt->error);
                    }

                    // Execute the statement
                    if (!$stmt->execute()) {
                        die("Execution failed: " . $stmt->error);
                    }

                    // Get the result
                    $result = $stmt->get_result();

                    // Define the path to the documents folder
                    $documentPath = "uploads/";

                    // Check if there are results
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row["category"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["pic"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["service"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["company"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["start"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["endDate"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["rent"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["remarks"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["monthsLeft"]) . "</td>";

                            // Check if 'filename' exists before accessing it
                            if (!empty($row['filename'])) {
                            echo "
                            <td><form action='view.php' method='get'>
                                <input type='hidden' name='filename' value='" . htmlspecialchars($row['filename']) . "'>
                                <button type='submit' class='btn'>View</button>
                              </form></td>";

                        } else {
                            echo "<td></td>";  // Empty cell without "Not available"
                        }
                            echo "</tr>";
                        }
                    }

                    // Close statements and connection
                    $stmt->close();
                    $connection->close();
                    ?>

                </tbody>

            </table>
        </div>
    </div>
</body>
</div>
</html>
