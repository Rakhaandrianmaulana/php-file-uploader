<?php
// =================================================================
// PERHATIAN: Skrip ini HANYA berfungsi di hosting tradisional 
// (cPanel/VPS) karena menggunakan penyimpanan disk lokal.
// =================================================================

// Tentukan direktori dasar untuk unggahan
$upload_dir_base = 'uploads/';

// Pesan status
$status_message = '';
$uploaded_url = '';

/**
 * Fungsi untuk menghasilkan ID unik (digunakan sebagai nama folder)
 */
function generateUniqueId($length = 8) {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

// Fungsi untuk mendapatkan URL dasar situs
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    return $protocol . $host . $uri . '/';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileToUpload'])) {
    $file = $_FILES['fileToUpload'];

    // 1. Validasi Unggahan
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $status_message = '<div class="text-red-600 font-semibold">Error Unggahan: ' . htmlspecialchars($file['error']) . '</div>';
    } else {
        // 2. Tentukan path unik
        $file_name = basename($file['name']);
        $unique_id = generateUniqueId();
        
        $target_dir = $upload_dir_base . $unique_id . '/';
        
        // Pastikan nama file bersih dari karakter berbahaya
        $safe_file_name = preg_replace("/[^a-zA-Z0-9\.\-_]/", "_", $file_name);
        $target_file = $target_dir . $safe_file_name;

        // 3. Buat direktori unik dan set izinnya 
        if (!is_dir($target_dir)) {
            // Izin 0777 diperlukan agar PHP dapat menulis
            if (!mkdir($target_dir, 0777, true)) { 
                $status_message = '<div class="text-red-600 font-semibold">Gagal membuat direktori unggahan! Periksa izin folder "uploads".</div>';
            }
        }

        // 4. Pindahkan file ke penyimpanan lokal
        if (empty($status_message)) {
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $uploaded_url = getBaseUrl() . $target_file;
                $status_message = '<div class="text-green-600 font-semibold">File berhasil diunggah ke server lokal!</div>';
            } else {
                $status_message = '<div class="text-red-600 font-semibold">Terjadi kesalahan saat memindahkan file.</div>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uploader File Lokal Sederhana</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7fafc;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-lg border border-gray-100">
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">
            Pengunggah Lokal (Manual Storage)
        </h1>
        <p class="text-sm text-gray-500 mb-6 text-center">
            Unggah file akan disimpan ke folder **uploads/** di server ini.
        </p>
        
        <!-- Form Unggahan -->
        <form action="uploader.php" method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="border-2 border-dashed border-indigo-300 rounded-lg p-6 hover:border-indigo-500 transition duration-300">
                <label for="fileToUpload" class="block text-lg font-medium text-gray-700 mb-2 cursor-pointer text-center">
                    Pilih File untuk Diunggah
                </label>
                <input type="file" name="fileToUpload" id="fileToUpload" required 
                       class="block w-full text-sm text-gray-500
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-full file:border-0
                              file:text-sm file:font-semibold
                              file:bg-indigo-50 file:text-indigo-700
                              hover:file:bg-indigo-100 cursor-pointer" />
            </div>
            
            <button type="submit" 
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-xl transition duration-300 transform hover:scale-[1.01] shadow-lg shadow-indigo-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Unggah Sekarang
            </button>
        </form>
        
        <?php if (!empty($status_message)): ?>
            <div class="mt-8 p-4 rounded-lg bg-gray-50 border <?php echo strpos($status_message, 'Error') !== false ? 'border-red-300' : 'border-green-300'; ?>">
                <?php echo $status_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($uploaded_url)): ?>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Tautan Unik Anda:</label>
                <div class="mt-1 flex rounded-lg shadow-sm">
                    <input type="text" id="uploadedUrl" readonly value="<?php echo htmlspecialchars($uploaded_url); ?>"
                           class="flex-1 min-w-0 block w-full px-3 py-2 rounded-l-lg border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-gray-800 bg-white"
                           onclick="this.select();">
                    <button id="copyButton" onclick="copyToClipboard()" type="button" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-r-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150">
                        Salin
                    </button>
                </div>
                <p class="text-xs text-gray-400 mt-2">Link mengarah ke folder unik di server Anda.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function copyToClipboard() {
            const copyText = document.getElementById("uploadedUrl");
            copyText.select();
            document.execCommand('copy');
            
            const button = document.getElementById("copyButton");
            const originalText = button.textContent;
            button.textContent = "Tersalin!";
            
            setTimeout(() => {
                button.textContent = originalText;
            }, 2000);
        }
    </script>
</body>
</html>
