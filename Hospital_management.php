<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hospital";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create Database and Table (Run only once)
$conn->query("CREATE DATABASE IF NOT EXISTS hospital");
$conn->select_db("hospital");

$conn->query("CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    contact VARCHAR(15) NOT NULL,
    address TEXT NOT NULL
)");

// Handling CRUD operations
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = isset($_POST['id']) ? trim($_POST['id']) : null;

    // Add Patient
    if (isset($_POST['add'])) {
        $name = trim($_POST['name']);
        $age = trim($_POST['age']);
        $gender = trim($_POST['gender']);
        $contact = trim($_POST['contact']);
        $address = trim($_POST['address']);

        if (!empty($name) && !empty($age) && !empty($gender) && !empty($contact) && !empty($address)) {
            $stmt = $conn->prepare("INSERT INTO patients (name, age, gender, contact, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sisss", $name, $age, $gender, $contact, $address);
            if ($stmt->execute()) {
                $message = "✅ Patient added successfully!";
            } else {
                $message = "❌ Error adding patient: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "⚠️ All fields are required!";
        }
    }

    // Update Patient (Only ID required, updates if fields are provided)
    if (isset($_POST['update'])) {
        if (!empty($id)) {
            $query = "UPDATE patients SET";
            $params = [];
            $types = "";

            if (!empty($_POST['name'])) { $query .= " name=?, "; $params[] = $_POST['name']; $types .= "s"; }
            if (!empty($_POST['age'])) { $query .= " age=?, "; $params[] = $_POST['age']; $types .= "i"; }
            if (!empty($_POST['gender'])) { $query .= " gender=?, "; $params[] = $_POST['gender']; $types .= "s"; }
            if (!empty($_POST['contact'])) { $query .= " contact=?, "; $params[] = $_POST['contact']; $types .= "s"; }
            if (!empty($_POST['address'])) { $query .= " address=?, "; $params[] = $_POST['address']; $types .= "s"; }

            $query = rtrim($query, ", ") . " WHERE id=?";
            $params[] = $id;
            $types .= "i";

            if (count($params) > 1) {
                $stmt = $conn->prepare($query);
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    $message = "✅ Patient updated successfully!";
                } else {
                    $message = "❌ Error updating patient: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = "⚠️ No new data provided for update!";
            }
        } else {
            $message = "⚠️ Patient ID is required for updating!";
        }
    }

    // Delete Patient (Only ID required)
    if (isset($_POST['delete'])) {
        if (!empty($id)) {
            $stmt = $conn->prepare("DELETE FROM patients WHERE id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = "✅ Patient deleted successfully!";
            } else {
                $message = "❌ Error deleting patient: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "⚠️ Patient ID is required for deletion!";
        }
    }
}

// Fetch all patients
$patients = $conn->query("SELECT * FROM patients");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Hospital Management</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; text-align: center; }
        .container { width: 50%; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); margin-top: 20px; text-align: left; }
        h2 { color: #2c3e50; text-align: center; }
        form { display: flex; flex-direction: column; gap: 10px; }
        label { font-weight: bold; display: block; }
        input, select, textarea { width: calc(100% - 22px); padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
        .btn-container { display: flex; justify-content: space-between; gap: 10px; margin-top: 10px; }
        .btn { flex: 1; padding: 10px; border: none; color: white; font-size: 16px; cursor: pointer; border-radius: 5px; transition: 0.3s; }
        .btn:hover { opacity: 0.8; }
        .btn-add { background: #2ecc71; }
        .btn-update { background: #f39c12; }
        .btn-delete { background: red; }
        .btn-display { background: darkred; }
        .message { color: green; font-weight: bold; margin-top: 10px; text-align: center; }
        table { width: 100%; margin-top: 20px; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; cursor: pointer; }
        th { background: #2c3e50; color: white; }
        tr:hover { background-color: #f1f1f1; }
    </style>
    <script>
        function fillForm(id) {
            document.querySelector("input[name='id']").value = id;
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>Hospital Management System</h2>

        <?php if ($message): ?>
            <p class="message"><?= $message ?></p>
        <?php endif; ?>

        <form action="" method="post">
            <label>ID (For Update/Delete):</label>
            <input type="number" name="id" placeholder="Enter ID if updating/deleting" required>

            <label>Name:</label>
            <input type="text" name="name">

            <label>Age:</label>
            <input type="number" name="age">

            <label>Gender:</label>
            <select name="gender">
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>

            <label>Contact:</label>
            <input type="text" name="contact">

            <label>Address:</label>
            <textarea name="address"></textarea>

            <div class="btn-container">
                <button type="submit" name="add" class="btn btn-add">Add</button>
                <button type="submit" name="update" class="btn btn-update">Update</button>
                <button type="submit" name="delete" class="btn btn-delete">Delete</button>
                <button type="submit" name="display" class="btn btn-delete">Display</button>
            </div>
        </form>

        <h2>Patient Records</h2>
        <table>
            <tr><th>ID</th><th>Name</th><th>Age</th><th>Gender</th><th>Contact</th><th>Address</th></tr>
            <?php while ($row = $patients->fetch_assoc()): ?>
                <tr onclick="fillForm('<?= $row['id'] ?>')">
                    <td><?= $row['id'] ?></td><td><?= $row['name'] ?></td><td><?= $row['age'] ?></td><td><?= $row['gender'] ?></td><td><?= $row['contact'] ?></td><td><?= $row['address'] ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
