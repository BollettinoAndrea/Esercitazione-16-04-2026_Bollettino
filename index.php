<?php
$conn= mysqli_connect("localhost", "root", "", "bollettino_biblioteca")
?>

<!DOCTYPE html>
<html>
<head>
    <title>Biblioteca</title>

<style>
body {
    font-family: Arial, sans-serif;
    background-color: #1267bd;
    margin: 0;
    padding: 20px;
}

h2, h3 {
    text-align: center;
    color: #333;
}

form {
    background: white;
    padding: 15px;
    margin: 20px auto;
    width: 350px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

input, select {
    width: 100%;
    padding: 8px;
    margin: 8px 0 12px 0;
    border: 1px solid #ccc;
    border-radius: 5px;
}

button {
    width: 100%;
    padding: 10px;
    background-color: #5da8fd;
    border: none;
    color: white;
    border-radius: 5px;
    cursor: pointer;
}

button:hover {
    background-color: #357abd;
}

table {
    margin: 20px auto;
    border-collapse: collapse;
    width: 60%;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

th, td {
    padding: 10px;
    text-align: center;
    border-bottom: 1px solid #ddd;
}

th {
    background-color: #60aaff;
    color: white;
}

tr:hover {
    background-color: #f1f1f1;
}

a {
    color: #4a90e2;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

hr {
    width: 60%;
    margin: 30px auto;
}
</style>

</head>
<body>

<h2>Inserisci Libro</h2>
<form method="POST">
    Titolo: <input type="text" name="titolo" required>

    Autore:
    <select name="autore">
        <?php
        $res = $conn->query("SELECT * FROM autori");
        while($row = $res->fetch_assoc()) {
            echo "<option value='{$row['id']}'>{$row['nome']}</option>";
        }
        ?>
    </select>

    <button type="submit" name="aggiungi_libro">Aggiungi</button>
</form>

<hr>

<h2>Inserisci Prestito</h2>
<form method="POST">

    Libro:
    <select name="libro">
        <?php
        $res = $conn->query("SELECT * FROM libri");
        while($row = $res->fetch_assoc()) {
            echo "<option value='{$row['id']}'>{$row['titolo']}</option>";
        }
        ?>
    </select>

    Utente:
    <select name="utente">
        <?php
        $res = $conn->query("SELECT * FROM utenti");
        while($row = $res->fetch_assoc()) {
            echo "<option value='{$row['id']}'>{$row['nome']}</option>";
        }
        ?>
    </select>

    <button type="submit" name="aggiungi_prestito">Presta</button>
</form>

<hr>

<h2>Visualizza Prestiti</h2>
<form method="GET">
    Utente:
    <select name="utente">
        <?php
        $res = $conn->query("SELECT * FROM utenti");
        while($row = $res->fetch_assoc()) {
            echo "<option value='{$row['id']}'>{$row['nome']}</option>";
        }
        ?>
    </select>

    <button type="submit">Visualizza</button>
</form>

<?php
if (isset($_GET['utente'])) {
    $id_utente = $_GET['utente'];

    $sql = "
    SELECT p.id, l.titolo, p.data_restituzione
    FROM prestiti p
    JOIN libri l ON p.id_libro = l.id
    WHERE p.id_utente = $id_utente
    ";

    $result = $conn->query($sql);

    echo "<h3>Prestiti:</h3>";
    echo "<table>
            <tr>
                <th>Libro</th>
                <th>Stato</th>
                <th>Azione</th>
            </tr>";

    while($row = $result->fetch_assoc()) {

        $stato = ($row['data_restituzione'] == NULL) ? "NON RESTITUITO" : "RESTITUITO";

        echo "<tr>";
        echo "<td>{$row['titolo']}</td>";
        echo "<td>$stato</td>";

        if ($row['data_restituzione'] == NULL) {
            echo "<td>
                    <a href='?utente=$id_utente&restituisci={$row['id']}'>
                        Restituisci
                    </a>
                  </td>";
        } else {
            echo "<td>-</td>";
        }

        echo "</tr>";
    }

    echo "</table>";
}
?>

</body>
</html>
