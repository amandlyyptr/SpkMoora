<?php
include './src/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['pesan'])) {
    $pesan = $_SESSION['pesan'];
    unset($_SESSION['pesan']);
}

if (empty($_SESSION['id'])) {
    header('location:./loginfirst.php');
    exit;
}

$query = "SELECT idhitung FROM tbhitung ORDER BY idhitung DESC LIMIT 1";
$result = $koneksi->query($query);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $idhitung = $row["idhitung"] + 1;
} else {
    $idhitung = 1;
}

if (isset($_GET['hal']) && $_GET['hal'] == "hapus") {
    $cek = mysqli_query($koneksi, "SELECT * FROM tbhitung WHERE idalternatif = '$_GET[id]'");
    $data = mysqli_fetch_array($cek);

    if ($data) {
        $hapus = mysqli_query($koneksi, "DELETE FROM tbhitung WHERE idalternatif = '$_GET[id]'");

        if ($hapus) {
            $_SESSION['pesan'] = 'hapus()';
        } else {
            $_SESSION['pesan'] = 'gagalhapus()';
        }

        header('location:./input.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPK | Metode MOOSRA</title>
    <?php include './src/header.php'; ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
</head>

<body onload="<?= @$pesan ?>" class="select-none">
    <?php include './src/navbar.php'; ?>

    <section id="isi">
        <form action="./src/aksicrud.php" method="post" class="max-w-5xl mx-auto mt-12">
            <div class="text-3xl text-transparent bg-clip-text bg-gradient-to-r from-primary via-secondary to-secondary font-bold">Penginputan Data</div>
            <hr class="w-12 h-1 outline-none border-none bg-gradient-to-r from-primary via-primary to-secondary rounded-full mb-4">
            <input type="text" name="tidhitung" class="input input-bordered hidden" value="<?= $idhitung ?>" />
            <div class="grid grid-cols-3 items-center justify-between gap-4">
                <div class="form-control col-span-3">
                    <label class="label">
                        <span class="label-text flex">Nama Universitas<div class="text-red-600">*</div></span>
                    </label>
                    <select name="tidalternatif" required class="select select-bordered">
                        <option value=""></option>
                        <?php
                        $query = "SELECT * FROM tbalternatif ORDER BY idalternatif ASC";
                        $result = mysqli_query($koneksi, $query);
                        while ($row = mysqli_fetch_array($result)) {
                            $idalternatif = $row['idalternatif'];
                            $namaalternatif = $row['namaalternatif'];
                            echo "<option value='$idalternatif'>$namaalternatif</option>";
                        }
                        ?>
                    </select>
                </div>
                <?php
                $query = "SELECT * FROM tbkriteria";
                $result = mysqli_query($koneksi, $query);
                while ($data = mysqli_fetch_array($result)) {
                ?>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text flex"><?= $data['namakriteria'] ?><div class="text-red-600">*</div></span>
                        </label>
                        <select name="<?= 'C' . $data['idkriteria'] ?>" required class="select select-bordered">
                            <option value=""></option>
                            <?php
                            $query = "SELECT * FROM tbsubkriteria WHERE idkriteria = '{$data['idkriteria']}'";
                            $results = mysqli_query($koneksi, $query);
                            while ($rows = mysqli_fetch_array($results)) {
                                $bobotsubkriteria = $rows['bobotsubkriteria'];
                                $namasubkriteria = $rows['namasubkriteria'];
                                echo "<option value='$bobotsubkriteria'>$namasubkriteria</option>";
                            }
                            ?>
                        </select>
                    </div>
                <?php } ?>
                <div class="col-span-3 w-full mx-auto flex items-center justify-center gap-3">
                    <button type="submit" name="binput" class="btn btn-primary">Simpan</button>
                    <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
            </div>
        </form>
    </section>

    <section id="tabel">
        <div class="overflow-x-auto max-w-5xl my-8 mx-auto dark:bg-base-300 shadow-md shadow-gray-700 p-8 rounded-lg">
            <table class="table text-center" id="myTable">
                <thead>
                    <tr>
                        <th style="display:none;">ID</th>
                        <th>Kode Alternatif</th>
                        <th>Nama Alternatif</th>
                        <?php
                        $columnsToShow = [];
                        for ($i = 1; $i <= 12; $i++) {
                            $columnHeader = 'C' . $i;
                            $q = "SELECT COUNT(*) FROM tbhitung WHERE $columnHeader > 0";
                            $query = mysqli_query($koneksi, $q);
                            $hasData = mysqli_fetch_array($query)[0] > 0;

                            if ($hasData) {
                                echo '<th>' . $columnHeader . '</th>';
                                $columnsToShow[] = $columnHeader;
                            }
                        }
                        ?>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $q = "SELECT * FROM tbhitung INNER JOIN tbalternatif ON tbhitung.idalternatif = tbalternatif.idalternatif ORDER BY tbhitung.idalternatif ASC";
                    $query = mysqli_query($koneksi, $q);
                    if (mysqli_num_rows($query) > 0) {
                        while ($data = mysqli_fetch_array($query)) {
                            echo "<tr>";
                            echo "<td style='display:none;'>" . $data['idalternatif'] . "</td>";
                            echo "<td>A" . $data['idalternatif'] . "</td>";
                            echo "<td>" . $data['namaalternatif'] . "</td>";
                            foreach ($columnsToShow as $columnName) {
                                echo "<td>" . $data[$columnName] . "</td>";
                            }
                            echo '<td><a href="./input.php?hal=hapus&id=' . $data['idalternatif'] . '" class="btn btn-sm btn-secondary">Hapus</a></td>';
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </section>

    <script>
        $(document).ready(function () {
            $('#myTable').DataTable({
                "order": [[0, "asc"]],
                "columnDefs": [
                    { "targets": 0, "visible": false, "searchable": false } // ID kolom untuk pengurutan saja
                ]
            });
        });
    </script>
</body>

</html>
