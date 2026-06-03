<?php
// connette al db
$conn = @new mysqli("db", "root", "root", "testdb"); // vulnerabile a tutto perche root
// $conn = @new mysqli("db", "least_privilege_user", "Password!2024", "testdb"); // utente con least privileges

$db_error = $conn->connect_error;

if (!$db_error) {
    // se si connette imposta i controlli sui collegamenti tra tabelle tramite chiavi esterne
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
}

$username = $_GET['username'] ?? '';
$password = $_GET['password'] ?? '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="style.css" />
</head>
<body>

    <div class="login-wrapper">
        <div class="image-div">
            <img src="login_image.png" alt="Login Illustration">
        </div>

        <div class="form-div">
            <h2>Welcome Back</h2>
            
            <form method="get" action="">
                <div class="input-group">
                    <i class="fa fa-user"></i>
                    <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                
                <div class="input-group">
                    <i class="fa fa-lock"></i>
                    <input type="password" name="password" class="pwd-input" placeholder="Password" required>
                    <i id="toggler" class="fa fa-eye"></i>
                </div>
                
                <button type="submit" class="btn-login">LOGIN</button>
            </form>
    
            <div class="forgot-link">
                <a href="#">Forgot Username / Password?</a>
            </div>

            <?php
            if (!empty($username) || !empty($password)) {
                
                // check se il db è connesso
                if ($db_error) {
                    echo '<div class="error-row"> Servizio temporaneamente non disponibile (DB Offline).</div>';
                } else {
                    // controlli della SQL injection
                    $tableExists = $conn->query("SHOW TABLES LIKE 'unsecure_users'")->num_rows > 0;
                    
                    if (!$tableExists) {
                        echo '<div class="error-row"></i> Accesso negato: Tabella "users" mancante o eliminata!</div>';
                    } else {
                        $query = "SELECT * FROM unsecure_users WHERE username = '$username' AND password = '$password'";
                        
                        if ($conn->multi_query($query)) {
                            $records_found = false;
                            echo '<div class="results-box">';
                            do {
                                if ($result = $conn->store_result()) {
                                    if ($result->num_rows > 0) {
                                        $records_found = true;
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<div class="success-row">';
                                            echo '<strong>Dato trovato:</strong> ' . htmlspecialchars(implode(" | ", $row));
                                            echo '</div>';
                                        }
                                    }
                                    $result->free();
                                }
                            } while ($conn->more_results() && $conn->next_result());
                            echo '</div>';

                            if (!$records_found) {
                                echo '<div class="error-row"></i> Invalid Email or Password.</div>';
                            }
                        } else {
                            echo '<div class="error-row"></i> Errore SQL: ' . $conn->error . '</div>';
                        }
                    }
                }
            }
            ?>

            <div class="create-account">
                <a href="#">Create your Account &rarr;</a>
            </div>
        </div>
    </div>

</body>
</html>
<?php
if (!$db_error && isset($conn)) {
    $conn->close();
}
?>
<script>
    // per l'occhio nella password cosi la rende visibile
    const toggler = document.getElementById('toggler');
    const passwordInput = document.querySelector('input[name="password"]');

    toggler.addEventListener('click', function () { // al click
        console.log("ciao");
        
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password'; // cambia attributo da text a password e viceversa
        passwordInput.setAttribute('type', type);
        
        // cambia l'icona dell'occhio
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
</script>