<?php

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "bollettino_biblioteca";

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($mysqli->connect_error) {
    die("Errore critico di connessione: " . $mysqli->connect_error);
}

$feedback = ["msg" => "", "tipo" => ""];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmd'])) {
    switch ($_POST['cmd']) {
        case 'add_book':
            $t = $mysqli->real_escape_string($_POST['t']);
            $a = intval($_POST['a']);
            $i = $mysqli->real_escape_string($_POST['i']);
            $auth = intval($_POST['auth']);
            $mysqli->query("INSERT INTO Libri (titolo, anno_pubblicazione, isbn, id_autore) VALUES ('$t', $a, '$i', $auth)");
            $feedback = ["msg" => "Nuovo volume aggiunto all'archivio.", "tipo" => "success"];
            break;

        case 'add_loan':
            $bk = intval($_POST['bk']);
            $usr = intval($_POST['usr']);
            $start = $_POST['s'];
            $end = $_POST['e'];
            $mysqli->query("INSERT INTO Prestiti (id_libro, id_utente, data_inizio, data_fine_prevista) VALUES ($bk, $usr, '$start', '$end')");
            $feedback = ["msg" => "Procedura di prestito completata.", "tipo" => "success"];
            break;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'return' && isset($_GET['pid'])) {
    $pid = intval($_GET['pid']);
    $mysqli->query("UPDATE Prestiti SET restituito = 1 WHERE id_prestito = $pid");
    $feedback = ["msg" => "Il libro è rientrato in biblioteca.", "tipo" => "info"];
}

$res_autori = $mysqli->query("SELECT id_autore, nome, cognome FROM Autori ORDER BY cognome ASC");
$res_libri  = $mysqli->query("SELECT id_libro, titolo FROM Libri ORDER BY titolo ASC");
$res_utenti = $mysqli->query("SELECT id_utente, nome, cognome FROM Utenti ORDER BY cognome ASC");
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Biblioteca</title>
    <style>
        :root {
            --bg: #0f172a;
            --card: #1e293b;
            --accent: #8b5cf6;
            --text: #f8fafc;
            --border: #334155;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', system-ui, sans-serif;
            margin: 0;
            padding: 40px 20px;
        }

        .wrapper {
            max-width: 1100px;
            margin: 0 auto;
        }

        .dashboard {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
        }

        .section {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 25px;
            flex: 1 1 400px;
        }

        .full-width {
            flex: 1 1 100%;
        }

        .hero {
            margin-bottom: 40px;
            border-left: 5px solid var(--accent);
            padding-left: 20px;
        }

        .input-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.9rem;
            color: #94a3b8;
        }

        input,
        select {
            width: 100%;
            padding: 12px;
            background: #0f172a;
            border: 1px solid var(--border);
            border-radius: 6px;
            color: white;
            box-sizing: border-box;
        }

        .btn {
            cursor: pointer;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            transition: 0.2s;
            width: 100%;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .notification {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            background: #1e293b;
            border-right: 4px solid #10b981;
        }

        .loan-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--border);
        }

        .badge {
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
        }

        .badge-active {
            background: #334155;
            color: #fbbf24;
        }

        .badge-done {
            background: #064e3b;
            color: #34d399;
        }

        .action-link {
            color: #8b5cf6;
            text-decoration: none;
            font-size: 0.85rem;
            border: 1px solid #8b5cf6;
            padding: 3px 8px;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<div class="wrapper">
    <header class="hero">
        <h1 style="margin:0">Archivio Digitale</h1>
        <p style="color: #64748b">Gestione flussi e catalogo bibliotecario</p>
    </header>

    <?php if($feedback['msg']): ?>
        <div class="notification"><?= $feedback['msg'] ?></div>
    <?php endif; ?>

    <main class="dashboard">
        
        <section class="section">
            <h2 style="color:var(--accent)">+ Inventario Libri</h2>
            <form method="POST">
                <input type="hidden" name="cmd" value="add_book">
                <div class="input-group">
                    <label>Titolo Opera</label>
                    <input type="text" name="t" required placeholder="Es. Il Nome della Rosa">
                </div>
                <div style="display:flex; gap:10px">
                    <div class="input-group" style="flex:1">
                        <label>Anno</label>
                        <input type="number" name="a" required>
                    </div>
                    <div class="input-group" style="flex:2">
                        <label>Codice ISBN</label>
                        <input type="text" name="i" required>
                    </div>
                </div>
                <div class="input-group">
                    <label>Autore</label>
                    <select name="auth">
                        <?php while($aut = $res_autori->fetch_assoc()): ?>
                            <option value="<?= $aut['id_autore'] ?>"><?= $aut['cognome'] . " " . $aut['nome'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Registra nel sistema</button>
            </form>
        </section>

        <section class="section">
            <h2 style="color:#f59e0b">+ Nuovo Prestito</h2>
            <form method="POST">
                <input type="hidden" name="cmd" value="add_loan">
                <div class="input-group">
                    <label>Seleziona Volume</label>
                    <select name="bk">
                        <?php while($bk = $res_libri->fetch_assoc()): ?>
                            <option value="<?= $bk['id_libro'] ?>"><?= $bk['titolo'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label>Assegna a Utente</label>
                    <select name="usr">
                        <?php while($u = $res_utenti->fetch_assoc()): ?>
                            <option value="<?= $u['id_utente'] ?>"><?= $u['cognome'] . " " . $u['nome'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div style="display:flex; gap:10px">
                    <div class="input-group" style="flex:1">
                        <label>Data Ritiro</label>
                        <input type="date" name="s" required>
                    </div>
                    <div class="input-group" style="flex:1">
                        <label>Rientro Previsto</label>
                        <input type="date" name="e" required>
                    </div>
                </div>
                <button type="submit" class="btn" style="background:#f59e0b; color:white">Avvia Transazione</button>
            </form>
        </section>

        <section class="section full-width">
            <h2>Monitoraggio Utenti</h2>
            <form method="GET" style="display: flex; gap: 15px; align-items: flex-end; margin-bottom: 30px;">
                <div style="flex-grow: 1;">
                    <label>Cerca Cronologia Utente:</label>
                    <select name="id_utente">
                        <option value="">-- Scegli un profilo --</option>
                        <?php 
                        $res_utenti->data_seek(0);
                        while($u = $res_utenti->fetch_assoc()): 
                            $sel = (isset($_GET['id_utente']) && $_GET['id_utente'] == $u['id_utente']) ? 'selected' : '';
                        ?>
                            <option value="<?= $u['id_utente'] ?>" <?= $sel ?>><?= $u['cognome'] . " " . $u['nome'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: auto;">Filtra Risultati</button>
            </form>

            <div class="results-list">
                <?php
                if(!empty($_GET['id_utente'])){
                    $target = intval($_GET['id_utente']);
                    $history = $mysqli->query("SELECT p.*, l.titolo FROM Prestiti p 
                                             INNER JOIN Libri l ON p.id_libro = l.id_libro 
                                             WHERE p.id_utente = $target ORDER BY p.data_inizio DESC");

                    if($history->num_rows > 0){
                        while($row = $history->fetch_assoc()){
                            echo "<div class='loan-row'>";
                            echo "<div><strong>{$row['titolo']}</strong><br><small style='color:#64748b'>Iniziato il: {$row['data_inizio']}</small></div>";
                            
                            if(!$row['restituito']){
                                echo "<div><span class='badge badge-active'>In possesso</span> ";
                                echo "<a href='?id_utente=$target&action=return&pid={$row['id_prestito']}' class='action-link'>Chiudi Prestito</a></div>";
                            } else {
                                echo "<div><span class='badge badge-done'>Archiviato</span></div>";
                            }
                            echo "</div>";
                        }
                    } else {
                        echo "<p style='text-align:center; color:#64748b; padding: 20px;'>Nessuna attività registrata per questo profilo.</p>";
                    }
                }
                ?>
            </div>
        </section>
    </main>
</div>

</body>
</html>