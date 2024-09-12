<?php
session_start();
require 'config.php'; // Connect to the database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['register_company'])) {
        $company_name = $_POST['company_name'];
        $address = $_POST['address'];
        $phone_number = $_POST['phone_number'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO companies (company_name, address, phone_number, email, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $company_name, $address, $phone_number, $email, $password);

        if ($stmt->execute()) {
            echo "Company saved.";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['register_agent'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone_number = $_POST['phone_number'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $company_id = $_SESSION['user']['id']; 

        $stmt = $conn->prepare("INSERT INTO sales_agents (name, email, password_hash, company_id, phone_number) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $name, $email, $password, $company_id, $phone_number);

        if ($stmt->execute()) {
            echo "Sales agent saved.";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $company_sql = "SELECT * FROM companies WHERE email=?";
        $agent_sql = "SELECT * FROM sales_agents WHERE email=?";

        $stmt = $conn->prepare($company_sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $company_result = $stmt->get_result();

        $stmt = $conn->prepare($agent_sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $agent_result = $stmt->get_result();

        if ($company_result->num_rows > 0) {
            $user = $company_result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user'] = $user;
                $_SESSION['role'] = 'company';
                header("Location: /YOUR_EXTENSION");
                exit();
            } else {
                echo "Wrong password.";
            }
        } elseif ($agent_result->num_rows > 0) {
            $user = $agent_result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user'] = $user;
                $_SESSION['role'] = 'agent';
                header("Location: /YOUR_EXTENSION");
                exit();
            } else {
                echo "Wrong password.";
            }
        } else {
            echo "No user found.";
        }
        $stmt->close();
    } elseif (isset($_POST['add_lead'])) {
        $sales_specialist_id = $_POST['sales_specialist_id'];
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];

        $stmt = $conn->prepare("INSERT INTO leads (sales_specialist_id, name, phone, email) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $sales_specialist_id, $name, $phone, $email);

        if ($stmt->execute()) {
            echo "Lead saved.";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['logout'])) {
        session_unset(); 
        session_destroy(); 
        header("Location: /YOUR_EXTENSION"); 
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Management</title>
    <link rel="stylesheet" href="styles.css">

</head>
    <body>
    <div>
    <div class="navbar">
        <img src="YOUR_LOGO_URL" alt="YOUR_COMPANY_NAME">
    </div>

    <?php if (!isset($_SESSION['user'])): ?>
        <h1>Sales Agent and Company Login</h1>

        <form method="POST">
            <h3>Register Company</h3>
            <input type="text" name="company_name" placeholder="Company name" required>
            <input type="text" name="address" placeholder="Address">
            <input type="text" name="phone_number" placeholder="Phone number">
            <input type="email" name="email" placeholder="E-mail" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="register_company">Save company</button>
        </form>

        <form method="POST">
            <h3>Login</h3>
            <input type="email" name="email" placeholder="E-mail" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>

    <?php else: ?>
        <?php $user = $_SESSION['user']; ?>

        <h2>Welcome, <?php echo ($_SESSION['role'] == 'company') ? $user['company_name'] : $user['name']; ?></h2>
        
        

        <div class="container">
            <div class="form-container">
                <?php if ($_SESSION['role'] == 'company'): ?>
                    <form method="POST">
                        <h3>Add sales agent</h3>
                        <input type="text" name="name" placeholder="Name" required>
                        <input type="number" name="phone_number" placeholder="Phone number" required>

                        <input type="email" name="email" placeholder="E-mail" required>
                        <input type="password" name="password" placeholder="Password" required>
                        <button type="submit" name="register_agent">Save sales agent</button>
                    </form>
                <?php endif; ?>

                <form method="POST">
                    <h3>Add lead</h3>
                    <?php if ($_SESSION['role'] == 'company'): ?>
                        <select name="sales_specialist_id" required>
                            <option value="">Choose sales agent</option>
                            <?php
                            $agents = $conn->query("SELECT id, name FROM sales_agents WHERE company_id = {$user['id']}");
                            while ($row = $agents->fetch_assoc()) {
                                echo "<option value='{$row['id']}'>{$row['name']}</option>";
                            }
                            ?>
                        </select>
                    <?php else: ?>
                        <input type="hidden" name="sales_specialist_id" value="<?php echo $user['id']; ?>">
                    <?php endif; ?>
                    <input type="text" name="name" placeholder="Name" required>
                    <input type="number" name="phone" placeholder="Phone number">
                    <input type="email" name="email" placeholder="E-mail" required>
                    <button type="submit" name="add_lead">Add lead</button>
                </form>
            </div>

            <div class="lead-container">
    <h2>Lead List</h2>
    <?php if ($_SESSION['role'] == 'company'): ?>
        <?php
        $agents = $conn->query("SELECT * FROM sales_agents WHERE company_id = {$user['id']}");
        while ($agent = $agents->fetch_assoc()) {
            
            echo "<h3>{$agent['name']}</h3>";
            echo "<p>{$agent['email']}</p>";
            echo "<p>{$agent['phone_number']}</p>";
            $leads = $conn->query("SELECT * FROM leads WHERE sales_specialist_id = {$agent['id']}");
            if ($leads->num_rows > 0) {
                echo "<table border='1' cellpadding='10'>";
                echo "<tr><th>Name</th><th>Phone number</th><th>Email</th></tr>";
                while ($lead = $leads->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$lead['name']}</td>";
                    echo "<td>{$lead['phone']}</td>";
                    echo "<td>{$lead['email']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No lead.</p>";
            }
        }
        ?>
    <?php else: ?>
        <?php
        $leads = $conn->query("SELECT * FROM leads WHERE sales_specialist_id = {$user['id']}");
        if ($leads->num_rows > 0) {
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>Name</th><th>Phone number</th><th>Email</th></tr>";
            while ($lead = $leads->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$lead['name']}</td>";
                echo "<td>{$lead['phone']}</td>";
                echo "<td>{$lead['email']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No lead.</p>";
        }
        ?>
    <?php endif; ?>
</div>
</div>

        <form method="POST" style = "width: 10%; "> 
            <button type="submit" name="logout" style = "background-color: #1b1b1b;">Logout</button>
        </form>

        
    <?php endif; ?>
    

</body>

</html>
