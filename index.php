<?php
session_start();
require 'config.php'; // Include database connection
require 'hubspot_functions.php'; // Include HubSpot functions

//Hold messages
$companyMessage = '';
$agentMessage = '';
$loginMessage = '';
$leadMessage = '';

// Company and sales agents registration 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['register_company'])) {
        // Register company
        $company_name = $_POST['company_name'];
        $address = $_POST['address'];
        $phone_number = $_POST['phone_number'];
        $email = $_POST['email'];
        $password = password_hash(password: $_POST['password'], algo: PASSWORD_DEFAULT);

        // Check if the email address is already registered
        $stmt = $conn->prepare(query: "SELECT * FROM companies WHERE email = ?");
        $stmt->bind_param(types: "s", var: $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $companyMessage = "This email address has already been registered.";
        } else {
            // Register the company
            $stmt = $conn->prepare(query: "INSERT INTO companies (company_name, address, phone_number, email, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $company_name, $address, $phone_number, $email, $password);

            if ($stmt->execute()) {
                $companyMessage = "The company has been registered. You can log in..";
                // Create a company in HubSpot and get the company ID
                $masterCompanyId = createHubSpotCompany($company_name,$address,$phone_number, $email);
            } else {
                $companyMessage = "Error: " . $stmt->error;
            }
        }
        $stmt->close();
    } elseif (isset($_POST['register_agent'])) {
        // Register sales agent
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone_number = $_POST['phone_number'];
        $password = password_hash(password: $_POST['password'], algo: PASSWORD_DEFAULT);
        $company_id = $_SESSION['user']['id'];  // Get the company ID from the session
        $company_address = '';  // It is left blank because we don't need it

        // Check if the email address or name is already registered
        $stmt = $conn->prepare(query: "SELECT * FROM sales_agents WHERE email = ? OR name = ?");
        $stmt->bind_param(types: "ss", var: $email, vars: $name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $agentMessage = "This email address has already been registered.";
        } else {
            // Register the sales agent
            $stmt = $conn->prepare(query: "INSERT INTO sales_agents (name, email, password_hash, company_id, phone_number) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssis", $name, $email, $password, $company_id, $phone_number);

            if ($stmt->execute()) {
                $agentMessage = "The sales agent has been registered.";
                $salesAgentCompanyId = createHubSpotCompany(companyName: $name, companyAddress: $company_address, companyPhone: $phone_number, companyEmail: $email);

                // Get the company ID by name
                $idResult = getCompanyIdByName(companyName: $_SESSION['user']['company_name']);
                
                // Associate the company with the sales agent
                associateCompanies(parentCompanyId: $idResult, childCompanyId: $salesAgentCompanyId);
            } else {
                $agentMessage = "Error: " . $stmt->error;
            }
        }
        $stmt->close();
    } elseif (isset($_POST['login'])) {
        // Login process
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Check if it is a company or a sales agent
        $company_sql = "SELECT * FROM companies WHERE email=?";
        $agent_sql = "SELECT * FROM sales_agents WHERE email=?";

        // Company check
        $stmt = $conn->prepare(query: $company_sql);
        $stmt->bind_param(types: "s", var: $email);
        $stmt->execute();
        $company_result = $stmt->get_result();

        // Sales agent check
        $stmt = $conn->prepare($agent_sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $agent_result = $stmt->get_result();

        if ($company_result->num_rows > 0) {
            $user = $company_result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Company login successful
                $_SESSION['user'] = $user;
                $_SESSION['role'] = 'company';
                header("Location: /EXTENSION_NAME");
                exit();
            } else {
                $loginMessage = "Wrong password.";
            }
        } elseif ($agent_result->num_rows > 0) {
            $user = $agent_result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                // Sales agent login successful
                $_SESSION['user'] = $user;
                $_SESSION['role'] = 'agent';
                header("Location: /EXTENSION_NAME");
                exit();
            } else {
                $loginMessage = "Wrong password.";
            }
        } else {
            $loginMessage = "No user found.";
        }
        $stmt->close();
    } elseif (isset($_POST['add_lead'])) {
    // Add Lead
    $sales_specialist_id = $_POST['sales_specialist_id']; 
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    // Add the lead to the database
    $stmt = $conn->prepare("INSERT INTO leads (sales_specialist_id, name, phone, email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $sales_specialist_id, $name, $phone, $email);

    if ($stmt->execute()) {
        $leadMessage = "Lead saved successfully.";

        // Create a contact in HubSpot and get the contact ID
        $contactId = createHubSpotContact($name, $phone, $email);

        // Find sales agent's name in database
        $stmt = $conn->prepare("SELECT name FROM sales_agents WHERE id = ?");
        $stmt->bind_param("i", $sales_specialist_id);
        $stmt->execute();
        $stmt->bind_result($agent_name, $agent_email);
        $stmt->fetch();
        $stmt->close();

        // Get the sales agent ID from HubSpot 
        $agentCompanyId = getCompanyIdByName($agent_name); 
        
        // Associate the contact with the sales agent
        associateContactWithCompany($contactId, $agentCompanyId);

    } else {
        $leadMessage = "Error: " . $stmt->error;
    }
    
} elseif (isset($_POST['logout'])) {
        // Logout Process
        session_unset(); // Unset all session variables
        session_destroy(); // Destroy the session
        header("Location: /EXTENSION_NAME"); // Redirect to the login page
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solution Partners</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="navbar">
        <img src="YOUR_LOGO_URL" alt="YOUR_NAME">
    </div>

    <?php if (!isset($_SESSION['user'])): ?>
        <!-- Show registration and login forms if not logged in -->
        <h1>Solution Partners Panel Login</h1>

        <!-- Company Registration Form -->
        <form style = "margin-top: 40px;" method="POST">
            <h3>Company Registration Form</h3>
            <input type="text" name="company_name" placeholder="Company name" required>
            <input type="text" name="address" placeholder="Address">
            <input type="text" name="phone_number" placeholder="Phone number">
            <input type="email" name="email" placeholder="E-mail" required>
            <input type="password" name="password" placeholder="Password" required>
            <button class = "save-button" type="submit" name="register_company">Register</button>
            <div class="message"><?php echo $companyMessage; ?></div>
        </form>
    
        <!-- Login Form -->
       <form style = "margin-top: 80px;" method="POST">
            <h3>Login</h3>
            <input type="email" name="email" placeholder="E-mail" required>
            <input type="password" name="password" placeholder="Password" required>
            <a class = "forgot-password" href="/forgot-password.php"> Forgot Password </a>
            <button  class = "save-button" type="submit" name="login">Login</button>
            <div class="message"><?php echo $loginMessage; ?></div>
            

        </form>

        
        
        

    <?php else: ?>
        <?php $user = $_SESSION['user']; ?>

        <div class = "page-header">
        <h2>Welcome, <?php echo ($_SESSION['role'] == 'company') ? $user['company_name'] : $user['name']; ?></h2>
        <!-- Logout -->
                <form method="POST" style = "width: 30%; "> 
                    <button type="submit" name="logout" style = "background-color: #1b1b1b;">Logout</button>
                </form>
        </div>
        

        <div class="container">
            <!-- Form fields on the right -->
            <div class="form-container">
                <?php if ($_SESSION['role'] == 'company'): ?>
                    <!-- Sales agent registration form if company login-->
                    <form method="POST">
                        <h3>Add Sales Agent</h3>
                        <input type="text" name="name" placeholder="Name" required>
                        <input type="number" name="phone_number" placeholder="Phone" required>

                        <input type="email" name="email" placeholder="E-mail" required>
                        <input type="password" name="password" placeholder="Password" required>
                        <button class = "save-button" type="submit" name="register_agent"> Save</button>
                        <div class="message"><?php echo $agentMessage; ?></div>
                    </form>
                <?php endif; ?>

                <!-- Add Lead Form-->
                <form method="POST">
                    <h3>Add Lead</h3>
                    <?php if ($_SESSION['role'] == 'company'): ?>
                        <!-- If company login, select sales agent -->
                        <select name="sales_specialist_id" required>
                            <option value="">Select sales agent</option>
                            <?php
                            $agents = $conn->query("SELECT id, name FROM sales_agents WHERE company_id = {$user['id']}");
                            while ($row = $agents->fetch_assoc()) {
                                echo "<option value='{$row['id']}'>{$row['name']}</option>";
                            }
                            ?>
                        </select>
                    <?php else: ?>
                        <!-- If the representative is logged in, use his/her own ID automatically -->
                        <input type="hidden" name="sales_specialist_id" value="<?php echo $user['id']; ?>">
                    <?php endif; ?>
                    <input type="text" name="name" placeholder="Name" required>
                    <input type="number" name="phone" placeholder="Phone">
                    <input type="email" name="email" placeholder="E-mail" required>
                    <button class = "save-button" type="submit" name="add_lead">Add Lead</button>
                    <div class="message"><?php echo $leadMessage; ?></div>
                </form>
                
                
        </div>

            <!-- Lead list on the right -->
            <div class="lead-container">
                <h2>Lead List</h2>
                <?php if ($_SESSION['role'] == 'company'): ?>
                    <!-- If the company is logged in, sales agents and leads -->
                    <?php
                    $agents = $conn->query("SELECT * FROM sales_agents WHERE company_id = {$user['id']}");
                    while ($agent = $agents->fetch_assoc()) {
                        
                        echo "<h4> Sales Agent: {$agent['name']}</h4> ";
                        echo "<p> Mail Address: {$agent['email']}</p>";
                        echo "<p> Phone Number: {$agent['phone_number']}</p>";
                        $leads = $conn->query("SELECT * FROM leads WHERE sales_specialist_id = {$agent['id']}");
                        if ($leads->num_rows > 0) {
                            echo "<table border='1' cellpadding='10'>";
                            echo "<tr><th>Name</th><th>Phone Number</th><th>Email</th></tr>";
                            while ($lead = $leads->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>{$lead['name']}</td>";
                                echo "<td>{$lead['phone']}</td>";
                                echo "<td>{$lead['email']}</td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                        } else {
                            echo "<p>No leads yet.</p>";
                        }
                    }
                    ?>
                <?php else: ?>
                    <!-- If the agent is logged in, its own leads -->
                    <?php
                    $leads = $conn->query("SELECT * FROM leads WHERE sales_specialist_id = {$user['id']}");
                    if ($leads->num_rows > 0) {
                        echo "<table border='1' cellpadding='10'>";
                        echo "<tr><th>Name</th><th>Phone Number</th><th>Email</th></tr>";
                        while ($lead = $leads->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>{$lead['name']}</td>";
                            echo "<td>{$lead['phone']}</td>";
                            echo "<td>{$lead['email']}</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<p>No leads yet.</p>";
                    }
                    ?>
                <?php endif; ?>
            </div>
    <?php endif; ?>
    
   
</body>

</html>
