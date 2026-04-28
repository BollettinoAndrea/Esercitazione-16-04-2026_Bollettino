<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "bollettino_biblioteca";

$istanza = new mysqli($host, $user, $pass, $db);

if ($istanza->connect_error) {
    die("Database non raggiungibile.");
}

$notifica = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task = $_POST['task'] ?? '';
    
    if ($task === 'new_book') {
        $titolo = $istanza->real_escape_string($_POST['title']);
        $anno = intval($_POST['year']);
        $isbn = $istanza->real_escape_string($_POST['isbn']);
        $aut = intval($_POST['author_id']);
        $istanza->query("INSERT INTO Libri (titolo, anno_pubblicazione, isbn, id_autore) VALUES ('$titolo', $anno, '$isbn', $aut)");
        $notifica = "Volume catalogato correttamente.";
    } 
    elseif ($task === 'new_loan') {
        $lib = intval($_POST['book_id']);
        $usr = intval($_POST['user_id']);
        $d1 = $_POST['start_date'];
        $d2 = $_POST['end_date'];
        $istanza->query("INSERT INTO Prestiti (id_libro, id_utente, data_inizio, data_fine_prevista) VALUES ($lib, $usr, '$d1', '$d2')");
        $notifica = "Transazione di prestito registrata.";
    }
}

if (isset($_GET['return_item'])) {
    $item_id = intval($_GET['return_item']);
    $istanza->query("UPDATE Prestiti SET restituito = 1 WHERE id_prestito = $item_id");
    $notifica = "Libro rientrato in sede.";
}

$libri_full = $istanza->query("SELECT L.*, A.nome, A.cognome FROM Libri L JOIN Autori A ON L.id_autore = A.id_autore ORDER BY L.id_libro DESC");
$prestiti_attivi = $istanza->query("SELECT P.*, L.titolo, U.nome, U.cognome FROM Prestiti P JOIN Libri L ON P.id_libro = L.id_libro JOIN Utenti U ON P.id_utente = U.id_utente ORDER BY P.restituito ASC, P.data_inizio DESC");

$lista_autori = $istanza->query("SELECT * FROM Autori");
$lista_libri = $istanza->query("SELECT * FROM Libri");
$lista_utenti = $istanza->query("SELECT * FROM Utenti");
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Controllo Biblioteca v3</title>
    <style>
        :root {
    --main: #3b82f6;
    --dark: #0b1220;
    --light: #e0f2fe;
    --gray: #cbd5e1;
}

body {
    font-family: 'Segoe UI', sans-serif;
    background: #f0f6ff; 
    color: var(--dark);
    margin: 0;
    padding: 0;
}

.container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 0 20px;
}

.nav-bar {
    background: var(--dark);
    color: white;
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 100;
}

.data-grid {
    display: grid;
    gap: 30px;
    margin-bottom: 50px;
}

.table-box {
    background: white;
    border-radius: 12px;
    box-shadow: 0 6px 14px -4px rgba(59,130,246,0.2);
    overflow: hidden;
}

.table-header {
    background: var(--main);
    color: white;
    padding: 15px 20px;
    font-weight: bold;
    font-size: 1.2rem;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: 12px 20px;
    text-align: left;
    border-bottom: 1px solid var(--gray);
}

th {
    background: #eff6ff;
    font-size: 0.85rem;
    text-transform: uppercase;
    color: #1e3a8a;
}

.badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-on {
    background: #dbeafe;
    color: #1d4ed8;
}

.badge-off {
    background: #bfdbfe;
    color: #1e3a8a;
}

.actions-area {
    background: var(--dark);
    color: white;
    padding: 60px 0;
    border-radius: 40px 40px 0 0;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 40px;
    margin-top: 30px;
}

.form-card {
    background: rgba(255,255,255,0.06);
    padding: 25px;
    border-radius: 15px;
    border: 1px solid rgba(59,130,246,0.25);
}

input,
select {
    width: 100%;
    padding: 10px;
    margin-top: 8px;
    margin-bottom: 15px;
    border-radius: 6px;
    border: none;
}

.btn-send {
    background: var(--main);
    color: white;
    border: none;
    padding: 12px;
    border-radius: 6px;
    cursor: pointer;
    width: 100%;
    font-weight: bold;
    transition: 0.2s;
}

.btn-send:hover {
    background: #2563eb;
}

.alert {
    background: #60a5fa;
    color: white;
    padding: 15px;
    text-align: center;
    border-radius: 8px;
    margin-bottom: 20px;
}

.btn-ret {
    color: var(--main);
    text-decoration: none;
    font-weight: bold;
    font-size: 0.9rem;
}
    </style>
</head>
<body>

<div class="nav-bar">
    <h2 style="margin:0">LibraryMonitor</h2>
    <span>Sistema Integrato Gestione</span>
</div>

<div class="container">
    <?php if($notifica): ?>
        <div class="alert"><?= $notifica ?></div>
    <?php endif; ?>

    <div class="data-grid">
        
        <div class="table-box">
            <div class="table-header">Registro Prestiti in Tempo Reale</div>
            <table>
                <thead>
                    <tr>
                        <th>Libro</th>
                        <th>Utente</th>
                        <th>Inizio</th>
                        <th>Stato</th>
                        <th>Azione</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($p = $prestiti_attivi->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= $p['titolo'] ?></strong></td>
                        <td><?= $p['nome'] . " " . $p['cognome'] ?></td>
                        <td><?= $p['data_inizio'] ?></td>
                        <td>
                            <span class="badge <?= $p['restituito'] ? 'badge-off' : 'badge-on' ?>">
                                <?= $p['restituito'] ? 'Restituito' : 'In Corso' ?>
                            </span>
                        </td>
                        <td>
                            <?php if(!$p['restituito']): ?>
                                <a href="?return_item=<?= $p['id_prestito'] ?>" class="btn-ret">Chiudi ora</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="table-box">
            <div class="table-header">Catalogo Libri</div>
            <table style="font-size: 0.9rem;">
                <thead>
                    <tr><th>Titolo</th><th>Autore</th><th>Anno</th><th>ISBN</th></tr>
                </thead>
                <tbody>
                    <?php while($l = $libri_full->fetch_assoc()): ?>
                    <tr>
                        <td><?= $l['titolo'] ?></td>
                        <td><?= $l['cognome'] ?></td>
                        <td><?= $l['anno_pubblicazione'] ?></td>
                        <td style="font-family: monospace;"><?= $l['isbn'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<div class="actions-area">
    <div class="container">
        <h2 style="text-align: center; color: var(--main);">Pannello Operativo</h2>
        <div class="form-grid">
            
            <div class="form-card">
                <h3>+ Aggiungi Volume</h3>
                <form method="POST">
                    <input type="hidden" name="task" value="new_book">
                    <label>Titolo</label><input type="text" name="title" required>
                    <label>Anno</label><input type="number" name="year" required>
                    <label>ISBN</label><input type="text" name="isbn" required>
                    <label>Autore</label>
                    <select name="author_id">
                        <?php while($a = $lista_autori->fetch_assoc()): ?>
                            <option value="<?= $a['id_autore'] ?>"><?= $a['nome'] ?> <?= $a['cognome'] ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn-send">Cataloga</button>
                </form>
            </div>

            <div class="form-card">
                <h3>+ Nuova Uscita</h3>
                <form method="POST">
                    <input type="hidden" name="task" value="new_loan">
                    <label>Libro</label>
                    <select name="book_id">
                        <?php while($lb = $lista_libri->fetch_assoc()): ?>
                            <option value="<?= $lb['id_libro'] ?>"><?= $lb['titolo'] ?></option>
                        <?php endwhile; ?>
                    </select>
                    <label>Utente</label>
                    <select name="user_id">
                        <?php while($us = $lista_utenti->fetch_assoc()): ?>
                            <option value="<?= $us['id_utente'] ?>"><?= $us['nome'] ?> <?= $us['cognome'] ?></option>
                        <?php endwhile; ?>
                    </select>
                    <label>Data Inizio</label><input type="date" name="start_date" required>
                    <label>Scadenza</label><input type="date" name="end_date" required>
                    <button type="submit" class="btn-send" style="background: #f59e0b;">Registra Uscita</button>
                </form>
            </div>

            <div class="form-card">
                <h3>Filtro Utente</h3>
                <form method="GET">
                    <label>Seleziona Utente per vedere solo i suoi prestiti:</label>
                    <select name="user_filter">
                        <?php 
                        $lista_utenti->data_seek(0);
                        while($us = $lista_utenti->fetch_assoc()): ?>
                            <option value="<?= $us['id_utente'] ?>"><?= $us['nome'] ?> <?= $us['cognome'] ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn-send" style="background: #3b82f6;">Applica Filtro</button>
                </form>
                <p style="font-size: 0.8rem; color: #94a3b8; margin-top: 15px;">
                    Nota: La tabella principale mostra tutti i movimenti. Usa questo campo per isolare un singolo iscritto.
                </p>
            </div>

        </div>
    </div>
</div>

</body>
</html> 
