# PHP Lead Management Project

This project is a lead management system built using PHP, allowing users to submit lead information via a form. The data is stored in a database, and existing leads are displayed on a dashboard.

## Features

- **Lead Form:** Users can add lead information including first name, last name, email, phone number, and address.
- **Database Integration:** Lead information is stored in a MySQL database.
- **Dashboard:** Displays a list of existing leads alongside the lead submission form.
- **Error and Success Messages:** Provides instant feedback to users during form submission (e.g., invalid inputs or successful submissions).
- **Responsive Design:** CSS ensures the layout adapts to different screen sizes and mobile devices.

## Requirements

To run this project, you will need the following:

- PHP 7.x or higher
- MySQL Database
- Apache or Nginx web server
- Internet browser for accessing the interface

## Installation

1. **Clone the project repository:**

   ```bash
   git clone https://github.com/username/repository-name.git
   cd repository-name

2. **Configure the database connection:**
   Edit the config.php file to set your database connection details.
    ````
    $servername = "localhost";
    $username = "your_db_username";
    $password = "your_db_password";
    $dbname = "your_db_name";
  3. **Create the database and table**
     ````
     CREATE TABLE companies (
        id INT(11) NOT NULL AUTO_INCREMENT,
        company_name VARCHAR(255) COLLATE latin1_swedish_ci NOT NULL,
        address VARCHAR(255) COLLATE latin1_swedish_ci DEFAULT NULL,
        phone_number VARCHAR(20) COLLATE latin1_swedish_ci DEFAULT NULL,
        email VARCHAR(255) COLLATE latin1_swedish_ci NOT NULL,
        password VARCHAR(255) COLLATE latin1_swedish_ci NOT NULL,
        PRIMARY KEY (id),
        UNIQUE INDEX email_idx (email)
     ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
     ````
     ````
     CREATE TABLE leads (
        id INT(11) NOT NULL AUTO_INCREMENT,
        sales_specialist_id INT(11) NOT NULL,
        name VARCHAR(255) COLLATE latin1_swedish_ci NOT NULL,
        phone VARCHAR(50) COLLATE latin1_swedish_ci DEFAULT NULL,
        city VARCHAR(100) COLLATE latin1_swedish_ci DEFAULT NULL,
        email VARCHAR(255) COLLATE latin1_swedish_ci DEFAULT NULL,
        PRIMARY KEY (id),
        INDEX sales_specialist_id_idx (sales_specialist_id),
        FOREIGN KEY (sales_specialist_id) REFERENCES sales_specialists(id) ON DELETE CASCADE
     ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
     ````
     ````
     CREATE TABLE sales_specialists (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) COLLATE latin1_swedish_ci NOT NULL,
        email VARCHAR(255) COLLATE latin1_swedish_ci NOT NULL,
        password_hash VARCHAR(255) COLLATE latin1_swedish_ci NOT NULL,
        company_id INT(11) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE INDEX email_idx (email),
        INDEX company_id_idx (company_id),
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
     ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
     ````

  5. **Start the PHP server**
  6. **Access the project interface**

 ## Technologies Used
  -**PHP**: For backend functionality 
  
  -**MySQL**: Database management
  
  -**CSS**: For responsive design
  
  -**HTML**: Structure of the form and dashboard

  ## Note
  If you want to add it as an extension to an existing website, don't forget to edit the htaccess file.
  ````
  RewriteEngine On
  RewriteRule ^EXTENSION_NAME/?$ /EXTENSION_NAME/index.php [L]
