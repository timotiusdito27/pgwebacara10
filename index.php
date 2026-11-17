<?php
// Koneksi ke basis data MySQL (XAMPP)
$host = "localhost";
$user = "root";
$pass = "";
$db   = "acara8";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi basis data gagal: " . $conn->connect_error);
}

// Proses Hapus Data
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $sql_delete = "DELETE FROM penduduk WHERE id = $id";
    if ($conn->query($sql_delete) === TRUE) {
        header("Location: " . strtok($_SERVER["URI"], "?")); // Refresh halaman
        exit();
    }
}

// Proses Edit / Update Data
if (isset($_POST['update'])) {
    $id        = intval($_POST['id']);
    $nama      = $conn->real_escape_string($_POST['nama']);
    $kecamatan = $conn->real_escape_string($_POST['kecamatan']);
    $latitude  = $conn->real_escape_string($_POST['latitude']);
    $longitude = $conn->real_escape_string($_POST['longitude']);

    $sql_update = "UPDATE penduduk SET nama='$nama', kecamatan='$kecamatan', latitude='$latitude', longitude='$longitude' WHERE id=$id";
    if ($conn->query($sql_update) === TRUE) {
        header("Location: " . strtok($_SERVER["URI"], "?"));
        exit();
    }
}

// Ambil data dari tabel penduduk
$sql = "SELECT * FROM penduduk";
$result = $conn->query($sql);

// Variabel untuk menampung data yang mau diedit jika tombol edit diklik
$edit_data = null;
if (isset($_GET['edit'])) {
    $id_edit = intval($_GET['edit']);
    $result_edit = $conn->query("SELECT * FROM penduduk WHERE id = $id_edit");
    if ($result_edit->num_rows > 0) {
        $edit_data = $result_edit->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web GIS - Acara 10 LeafletJS (Edit & Hapus)</title>
    
    <!-- Leaflet CSS Library -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            font-family: Arial, sans-serif;
        }
        #map {
            flex: 2;
            height: 100%;
        }
        #sidebar {
            flex: 1;
            height: 100%;
            overflow-y: auto;
            padding: 15px;
            background: #f9f9f9;
            box-shadow: -2px 0 5px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: #fff;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            font-size: 11px;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        .btn {
            padding: 4px 8px;
            font-size: 10px;
            text-decoration: none;
            color: white;
            border-radius: 3px;
            border: none;
            cursor: pointer;
        }
        .btn-edit { background-color: #ffc107; color: black; }
        .btn-delete { background-color: #dc3545; }
        .btn-submit { background-color: #28a745; width: 100%; padding: 8px; margin-top: 5px; }
        .form-edit {
            background: #fff;
            padding: 10px;
            margin-top: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .form-edit input {
            width: 100%;
            padding: 5px;
            margin-bottom: 5px;
            box-sizing: border-box;
            font-size: 11px;
        }
    </style>
</head>
<body>

    <!-- Tampilan Peta Web GIS -->
    <div id="map"></div>

    <!-- Sidebar & Tabel Data -->
    <div id="sidebar">
        <h3>Sebaran Usaha Kuliner di Kota Bandung</h3>
        <p>Sumber data dari: <b>Bandung Geodata</b></p>

        <?php if ($edit_data): ?>
        <!-- Form Edit Data -->
        <div class="form-edit">
            <h4>Edit Data Penduduk/Usaha</h4>
            <form method="POST" action="">
                <input type="hidden" name="id" value="<?= $edit_data['id']; ?>">
                <label>Nama:</label>
                <input type="text" name="nama" value="<?= htmlspecialchars($edit_data['nama']); ?>" required>
                <label>Kecamatan:</label>
                <input type="text" name="kecamatan" value="<?= htmlspecialchars($edit_data['kecamatan']); ?>" required>
                <label>Latitude:</label>
                <input type="text" name="latitude" value="<?= $edit_data['latitude']; ?>" required>
                <label>Longitude:</label>
                <input type="text" name="longitude" value="<?= $edit_data['longitude']; ?>" required>
                <button type="submit" name="update" class="btn btn-submit">Simpan Perubahan</button>
                <a href="?" style="font-size: 11px; display:block; text-align:center; margin-top:5px;">Batal</a>
            </form>
        </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Kecamatan</th>
                    <th>Lat/Lng</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $markers_data = [];
                // Reset pointer atau query ulang jika diperlukan, tapi kita pakai $result dari atas
                if ($result->num_rows > 0) {
                    // Karena pointer result sudah dipakai di atas untuk edit data, kita query ulang atau pastikan data aman
                    $result = $conn->query($sql); 
                    while($row = $result->fetch_assoc()) {
                        $markers_data[] = $row;
                        echo "<tr>
                                <td>{$no}</td>
                                <td>" . htmlspecialchars($row['nama']) . "</td>
                                <td>" . htmlspecialchars($row['kecamatan']) . "</td>
                                <td style='font-size:9px;'>{$row['latitude']}<br>{$row['longitude']}</td>
                                <td>
                                    <a href='?edit={$row['id']}' class='btn btn-edit'>Edit</a>
                                    <a href='?hapus={$row['id']}' class='btn btn-delete' onclick='return confirm(\"Yakin ingin menghapus data ini?\");'>Hapus</a>
                                </td>
                              </tr>";
                        $no++;
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center;'>Tidak ada data.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Leaflet JavaScript Library -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        /* Inisialisasi Peta */
        var map = L.map('map').setView([-6.914744, 107.609810], 12);

        /* Basemaps */
        var basemap1 = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri'
        });

        var basemap2 = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: 'Map data &copy; OpenStreetMap contributors'
        });

        basemap1.addTo(map);

        /* Layer Group untuk Marker Database */
        var layerDatabase = L.layerGroup();

        /* Masukkan Data Koordinat dari PHP ke Marker Leaflet */
        <?php foreach ($markers_data as $data): ?>
            var marker = L.marker([<?= $data['latitude']; ?>, <?= $data['longitude']; ?>])
                .bindPopup("<b><?= addslashes($data['nama']); ?></b><br>Kecamatan: <?= addslashes($data['kecamatan']); ?>");
            layerDatabase.addLayer(marker);
        <?php endforeach; ?>

        layerDatabase.addTo(map);

        /* Kontrol Layer */
        var baseMaps = {
            "Esri Imagery": basemap1,
            "OpenStreetMap": basemap2
        };

        var overlayMaps = {
            "Titik Usaha": layerDatabase
        };

        L.control.layers(baseMaps, overlayMaps, { collapsed: false }).addTo(map);

        /* Scale Bar */
        L.control.scale({
            maxWidth: 150, 
            position: 'bottomright'
        }).addTo(map);
    </script>
</body>
</html>