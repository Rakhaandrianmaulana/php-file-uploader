<?php
// Pastikan Composer Autoload dimuat untuk menggunakan Cloudinary SDK
// Ini memerlukan folder vendor/ yang dihasilkan oleh "composer install"
require __DIR__ . '/vendor/autoload.php';

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;

// =================================================================
// PENTING: KODE INI HANYA BERFUNGSI JIKA ANDA SUDAH MENGATUR 
// VARIABEL LINGKUNGAN CLOUDINARY_URL LENGKAP DI DASBOR VERCEL
// =================================================================

// Pesan status dan URL
$status_message = '';
$uploaded_url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileToUpload'])) {
    $file = $_FILES['fileToUpload'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $status_message = '<div class="text-red-600 font-semibold">Error Unggahan: ' . htmlspecialchars($file['error']) . '</div>';
    } else {
        // Mendapatkan URL Konfigurasi dari Vercel Environment Variables
        // Format CLOUDINARY_URL: cloudinary://<API_KEY>:<API_SECRET>@<CLOUD_NAME>
        $cloudinary_url = getenv('CLOUDINARY_URL');

        if (!$cloudinary_url) {
            $status_message = '<div class="text-red-600 font-semibold">ERROR: Variabel lingkungan CLOUDINARY_URL belum diatur di Vercel. Silakan atur di Settings > Environment Variables.</div>';
        } else {
            try {
                // 1. Konfigurasi Cloudinary dari Environment Variable
                $config = Configuration::instance($cloudinary_url);
                $cloudinary = new Cloudinary($config);

                // 2. Tentukan nama file yang akan digunakan sebagai Public ID di Cloudinary
                $public_id_base = pathinfo($file['name'], PATHINFO_FILENAME);
                $unique_tag = substr(md5(uniqid(rand(), true)), 0, 8);
                // Menentukan folder khusus di Cloudinary
                $folder_path = "vercel_uploader_app/{$unique_tag}";

                // 3. Unggah file
                // Cloudinary secara otomatis menangani file dari temporary path
                $result = $cloudinary->uploadApi()->upload($file['tmp_name'], [
                    'folder' => $folder_path,
                    'public_id' => $public_id_base,
                    'resource_type' => 'auto',
                    'unique_filename' => true 
                ]);

                // Mendapatkan URL file yang diunggah
                $uploaded_url = $result['secure_url'];
                $status_message = '<div class="text-green-600 font-semibold">File <strong>' . htmlspecialchars($file['name']) . '</strong> berhasil diunggah ke Cloudinary!</div>';

            } catch (\Exception $e) {
                // Tangani kesalahan koneksi atau upload Cloudinary
                $status_message = '<div class="text-red-600 font-semibold">Gagal mengunggah ke Cloudinary. Cek CLOUDINARY_URL Anda: ' . htmlspecialchars($e->getMessage()) . '</div>';
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
    <title>Cloudinary Uploader Vercel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #eef2ff; /* Light indigo background */
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-lg border border-indigo-100">
        <h1 class="text-3xl font-extrabold text-indigo-700 mb-6 text-center">
            Cloudinary Uploader (Vercel)
        </h1>
        <p class="text-sm text-gray-500 mb-6 text-center">
            Mengunggah file ke Cloudinary dan menghasilkan URL publik.
        </p>
        
        <!-- Status Message -->
        <?php if (!empty($status_message)): ?>
            <div class="mt-4 p-4 rounded-xl shadow-md border <?php echo strpos($status_message, 'Error') !== false ? 'bg-red-50 border-red-300' : 'bg-green-50 border-green-300'; ?>">
                <?php echo $status_message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Form Unggahan -->
        <form action="uploader.php" method="POST" enctype="multipart/form-data" class="space-y-6 mt-6">
            <div class="border-2 border-dashed border-indigo-300 rounded-xl p-6 hover:border-indigo-500 transition duration-300 bg-gray-50">
                <label for="fileToUpload" class="block text-lg font-medium text-gray-700 mb-2 cursor-pointer text-center">
                    Pilih File untuk Diunggah
                </label>
                <input type="file" name="fileToUpload" id="fileToUpload" required 
                       class="block w-full text-sm text-gray-500
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-full file:border-0
                              file:text-sm file:font-semibold
                              file:bg-indigo-100 file:text-indigo-700
                              hover:file:bg-indigo-200 cursor-pointer" />
            </div>
            
            <button type="submit" 
                    class="w-full bg-indigo-600 hover:bg-indigo-800 text-white font-bold py-3 px-4 rounded-xl transition duration-300 transform hover:scale-[1.01] shadow-xl shadow-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500 focus:ring-opacity-50">
                Unggah ke Cloudinary
            </button>
        </form>
        
        
        <?php if (!empty($uploaded_url)): ?>
            <div class="mt-8 pt-4 border-t border-gray-100">
                <label class="block text-base font-bold text-gray-800 mb-2">Tautan Publik Cloudinary:</label>
                <div class="mt-1 flex rounded-lg shadow-sm">
                    <input type="text" id="uploadedUrl" readonly value="<?php echo htmlspecialchars($uploaded_url); ?>"
                           class="flex-1 min-w-0 block w-full px-4 py-2 rounded-l-lg border-2 border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm bg-indigo-50"
                           onclick="this.select();">
                    <button id="copyButton" onclick="copyToClipboard()" type="button" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-r-lg shadow-md text-white bg-indigo-600 hover:bg-indigo-700 transition duration-150">
                        Salin
                    </button>
                </div>
                <p class="text-xs text-gray-400 mt-2">URL ini menunjuk ke file Anda di Cloudinary.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function copyToClipboard() {
            const copyText = document.getElementById("uploadedUrl");
            copyText.select();
            copyText.setSelectionRange(0, 99999); 
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
