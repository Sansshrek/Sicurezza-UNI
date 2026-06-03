<?php
// connette al db
// $conn = @new mysqli("db", "root", "root", "testdb"); // vulnerabile a tutto perche root
$conn = @new mysqli("db", "least_privilege_user", "Password!2026", "testdb"); // utente con least privileges

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
                    $tableExists = $conn->query("SHOW TABLES LIKE 'secure_users'")->num_rows > 0;
                    
                    if (!$tableExists) {
                        echo '<div class="error-row"></i> Accesso negato: Tabella "users" mancante o eliminata!</div>';
                    } else {
    
                        // uso delle query parametrizzate e togliamo il multi_query() cosi non si possono piu accodare query
                        $query = $conn->prepare("SELECT * FROM secure_users WHERE username = ?");
                        
                        if ($query) {
                            $query->bind_param("s", $username); // colleghiamo l'input dell'utente al posto del ?
                            $query->execute();
                            $result = $query->get_result();

                            $records_found = false;
                            echo '<div class="results-box">';

                            if ($row = $result->fetch_assoc()) { // se esiste l'utente nel db
                                if (password_verify($password, $row['password'])) { // facciamo il check della password hashata
                                    $records_found = true; // login effettuato
                                    echo '<div class="success-row">';
                                    echo '<strong>Dato trovato:</strong> ' . htmlspecialchars(implode(" | ", $row));
                                    echo '</div>';
                                }
                            } 
                            echo '</div>';

                            if (!$records_found) {
                                echo '<div class="error-row"></i> Invalid Email or Password.</div>';
                            }

                            $query->close();
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
        console.log("ciao1");
        
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password'; // cambia attributo da text a password e viceversa
        passwordInput.setAttribute('type', type);
        
        // cambia l'icona dell'occhio
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
</script>