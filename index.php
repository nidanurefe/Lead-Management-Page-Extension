<?php
session_start();

require 'config.php';  // Inherit database connection from config file

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Insert company information into the database
    if (isset($_POST['register_company'])) {
        $company_name = $_POST['company_name'];
        $address = $_POST['address'];
        $phone_number = $_POST['phone_number'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO companies (company_name, address, phone_number, email, password) 
                VALUES ('$company_name', '$address', '$phone_number', '$email', '$password')";

        if ($conn->query($sql) === TRUE) {
            echo "Company Saved.";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }

    // Insert sales agent information into the database
    } elseif (isset($_POST['register_specialist'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $company_id = $_POST['company_id'];

        $sql = "INSERT INTO sales_specialists (name, email,  password_hash, company_id) 
                VALUES ('$name', '$email',  '$password', '$company_id')";

        if ($conn->query($sql) === TRUE) {
            echo "Sales agent saved.";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }

    // Login company or sales agent
    } elseif (isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $company_sql = "SELECT * FROM companies WHERE email='$email'";
        $specialist_sql = "SELECT * FROM sales_specialists WHERE email='$email'";
        $company_result = $conn->query($company_sql);
        $specialist_result = $conn->query($specialist_sql);

        if ($company_result->num_rows > 0) {
            $user = $company_result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user'] = $user;
                $_SESSION['role'] = 'company';
                header("Location: /sales-agents");
                exit();
            } else {
                echo "Wrong password.";
            }
        } elseif ($specialist_result->num_rows > 0) {
            $user = $specialist_result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user'] = $user;
                $_SESSION['role'] = 'specialist';
                header("Location: /sales-agents");
                exit();
            } else {
                echo "Wrong password.";
            }
        } else {
            echo "No user found.";
        }

    // Insert lead information into the database
    } elseif (isset($_POST['add_lead'])) {
        $sales_specialist_id = $_POST['sales_specialist_id'];
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];

        $sql = "INSERT INTO leads (sales_specialist_id, name, phone, email) 
                VALUES ('$sales_specialist_id', '$name', '$phone', '$email')";

        if ($conn->query($sql) === TRUE) {
            echo "Lead saved.";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
    elseif (isset($_POST['logout'])) {
        session_unset(); 
        session_destroy(); 
        header("Location: /sales-agents"); 
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Management</title>
    <link rel="stylesheet" href="styles.css">
<body>
    <div class="navbar">
    <img src="ENTER_YOUR_COMPANY_LOGO" alt="company_logo">
</div>

<?php if (!isset($_SESSION['user'])): ?>
    <h1>Lead Management</h1>
    
    <!-- Company Register Form -->
    <form method="POST">
        <h3>Company Register</h3>
        <input type="text" name="company_name" placeholder="Company Name" required>
        <input type="text" name="address" placeholder="Address">
        <input type="text" name="phone_number" placeholder="Phone number">
        <input type="email" name="email" placeholder="E-mail" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="register_company">Save company</button>
    </form>

     <!-- Sales Agent Register Form -->
    <form method="POST">
        <h3>Sales Agent Register</h3>
        <input type="text" name="name" placeholder="Name" required>
        <input type="email" name="email" placeholder="E-mail" required>
        <input type="text" name="phone_number" placeholder="Phone number">
        <input type="password" name="password" placeholder="Password" required>
        <select name="company_id">
            <option value="">Select Company</option>
            <?php
            $companies = $conn->query("SELECT id, company_name FROM companies");
            while ($row = $companies->fetch_assoc()) {
                echo "<option value='{$row['id']}'>{$row['company_name']}</option>";
            }
            ?>
        </select>
        <button type="submit" name="register_specialist">Save Sales Agent</button>
    </form>

    <!-- Login Form -->
    <form method="POST">
        <h3>Login</h3>
        <input type="email" name="email" placeholder="E-mail" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>

<?php else: ?>
    <?php $user = $_SESSION['user']; ?>
    <?php
    if ($_SESSION['role'] == 'specialist') {
        $specialist_id = $_SESSION['user']['id'];
        $sql = "SELECT companies.company_name, companies.address, companies.phone_number 
                FROM companies 
                JOIN sales_specialists ON companies.id = sales_specialists.company_id 
                WHERE sales_specialists.id = $specialist_id";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $company = $result->fetch_assoc();
            $company_name = $company['company_name'];
            $address = $company['address'];
            $phone_number = $company['phone_number'];
        } else {
            $company_name = "No company found";
            $address = "No address found";
            $phone_number = "No phone number found";
        }
    } else {
        $company_name = $_SESSION['user']['company_name'];
        $address = $_SESSION['user']['address'];
        $phone_number = $_SESSION['user']['phone_number'];
    }
    ?>

    <h2>Company: <?php echo $company_name; ?></h2>
    <?php echo $phone_number; ?>
    

    <div class="container">
        <div class="form-container representatives-leads-container">
            <h3>Add Lead</h3>
            <form method="POST">
                <?php if ($_SESSION['role'] == 'company'): ?>
                    <select name="sales_specialist_id" required>
                        <option value="">Select sales agent</option>
                        <?php
                        $specialists = $conn->query("SELECT id, name FROM sales_specialists WHERE company_id = {$user['id']}");
                        while ($row = $specialists->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>{$row['name']}</option>";
                        }
                        ?>
                    </select>
                <?php elseif ($_SESSION['role'] == 'specialist'): ?>
                    <input type="hidden" name="sales_specialist_id" value="<?php echo $_SESSION['user']['id']; ?>">
                    <p>Sales agent: <?php echo $_SESSION['user']['name']; ?></p>
                <?php endif; ?>

                <input type="text" name="name" placeholder="Name" required>
                <input type="text" name="phone" placeholder="Phone number">
                <input type="email" name="email" placeholder="E-mail">
                <button type="submit" name="add_lead">Add lead</button>
            </form>
        </div>

        <div class="list-container representatives-leads-container">
            <h3>Sales Agents and Leads</h3>
            <table>
                <tbody>
                    <?php
                    if ($_SESSION['role'] == 'company') {
                        $specialists = $conn->query("SELECT * FROM sales_specialists WHERE company_id = {$_SESSION['user']['id']}");
                        while ($specialist = $specialists->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td colspan='3' style='font-weight: bold;'>{$specialist['name']}</td>";
                            echo "</tr>";

                            $leads = $conn->query("SELECT * FROM leads WHERE sales_specialist_id = {$specialist['id']}");
                            while ($lead = $leads->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>{$lead['name']}</td>";
                                echo "<td>{$lead['email']}</td>";
                                echo "<td>{$lead['phone']}</td>";
                                echo "</tr>";
                            }
                        }
                    } elseif ($_SESSION['role'] == 'specialist') {
                        $specialist_id = $_SESSION['user']['id'];
                        $leads = $conn->query("SELECT * FROM leads WHERE sales_specialist_id = $specialist_id");
                        while ($lead = $leads->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>{$lead['name']}</td>";
                            echo "<td>{$lead['email']}</td>";
                            echo "<td>{$lead['phone']}</td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>

            <form method="POST" style="width: 130px;">
                <button type="submit"  style="width: 130px;" name="logout">Logout</button>
            </form>
        </div>
    </div>
<?php endif; ?>

</body>
</html>
