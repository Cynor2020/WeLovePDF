<?php
// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/pdf_protection_errors.log');

// Register shutdown function to catch fatal errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Shutdown error: {$error['message']} in {$error['file']} on line {$error['line']}");
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Fatal error: ' . $error['message']
        ]);
        exit;
    }
});

// Handle download request
if (isset($_GET['download']) && isset($_GET['file'])) {
    error_log("Processing download request for file: " . $_GET['file']);
    $protectedDir = __DIR__ . '/protected/';
    $file = basename($_GET['file']);
    $filePath = $protectedDir . $file;
    
    if (file_exists($filePath)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="protected_' . $file . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
    
    error_log("Download file not found: $filePath");
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'File not found']);
    exit;
}

// Handle PDF upload and protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start output buffering
    ob_start();
    
    try {
        error_log("Starting PDF protection process");
        
        // Test mode for debugging (bypass PDF processing)
        if (isset($_GET['test_mode'])) {
            error_log("Running in test mode");
            throw new Exception('Test mode enabled');
        }

        // Check if Composer autoload exists
        $autoloadPath = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            throw new Exception('Composer dependencies not installed. Run "composer require setasign/fpdi setasign/fpdi-tcpdf"');
        }
        
        error_log("Loading Composer autoloader: $autoloadPath");
        require $autoloadPath;
        
        // Create directories
        $uploadDir = __DIR__ . '/uploads/';
        $protectedDir = __DIR__ . '/protected/';
        
        error_log("Checking directories: $uploadDir, $protectedDir");
        if (!@mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new Exception('Failed to create uploads directory');
        }
        if (!@mkdir($protectedDir, 0755, true) && !is_dir($protectedDir)) {
            throw new Exception('Failed to create protected directory');
        }

        // Validate input
        if (!isset($_FILES['pdfFile'])) {
            throw new Exception('No PDF file uploaded');
        }
        
        if (!isset($_POST['password']) || empty($_POST['password'])) {
            throw new Exception('Password is required');
        }

        $password = $_POST['password'];
        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters long');
        }

        $pdfFile = $_FILES['pdfFile'];
        
        // Validate PDF file
        error_log("Validating uploaded file: {$pdfFile['name']}");
        if ($pdfFile['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'File too large (exceeds upload_max_filesize)',
                UPLOAD_ERR_FORM_SIZE => 'File too large (exceeds form limit)',
                UPLOAD_ERR_PARTIAL => 'File upload was interrupted',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded'
            ];
            throw new Exception($errors[$pdfFile['error']] ?? 'File upload error: ' . $pdfFile['error']);
        }
        
        $fileExt = strtolower(pathinfo($pdfFile['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'pdf') {
            throw new Exception('Only PDF files are allowed');
        }

        // Generate unique filename
        $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $pdfFile['name']);
        while (file_exists($uploadDir . $fileName)) {
            $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $pdfFile['name']);
        }
        $uploadPath = $uploadDir . $fileName;
        
        error_log("Saving uploaded file to: $uploadPath");
        if (!move_uploaded_file($pdfFile['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to save uploaded file. Check directory permissions.');
        }

        // Initialize FPDI (try TCPDF first, fallback to basic FPDI)
        error_log("Initializing FPDI");
        if (class_exists('setasign\Fpdi\Tcpdf\Fpdi')) {
            $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
            error_log("Using FPDI with TCPDF");
        } elseif (class_exists('setasign\Fpdi\Fpdi')) {
            $pdf = new \setasign\Fpdi\Fpdi();
            error_log("Using basic FPDI (TCPDF not available)");
        } else {
            throw new Exception('FPDI class not found. Ensure Composer dependencies are installed.');
        }
        
        // Set source PDF file
        error_log("Setting source file: $uploadPath");
        $pageCount = $pdf->setSourceFile($uploadPath);
        error_log("Page count: $pageCount");
        if ($pageCount === 0) {
            unlink($uploadPath);
            throw new Exception('The PDF file is empty or corrupted');
        }
        
        // Import each page
        for ($i = 1; $i <= $pageCount; $i++) {
            error_log("Importing page $i");
            $templateId = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($templateId);
            
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }
        
        // Set PDF protection
        error_log("Setting PDF protection");
        $pdf->SetProtection(
            ['print', 'modify', 'copy', 'annot-forms'],
            $password, // User password
            $password, // Owner password
            3 // 128-bit encryption
        );
        
        // Save protected PDF
        $protectedFilename = 'protected_' . $fileName;
        $protectedPath = $protectedDir . $protectedFilename;
        error_log("Saving protected PDF to: $protectedPath");
        $pdf->Output($protectedPath, 'F');
        
        // Clean up original file
        unlink($uploadPath);
        
        // Clean up old protected files (older than 24 hours)
        error_log("Cleaning up old protected files");
        $files = glob($protectedDir . '*.pdf');
        foreach ($files as $file) {
            if (filemtime($file) < time() - 24 * 3600) {
                unlink($file);
            }
        }
        
        // Prepare response
        $response = [
            'success' => true,
            'message' => 'PDF protected successfully',
            'downloadUrl' => '?download=true&file=' . $protectedFilename
        ];
        
    } catch (Exception $e) {
        // Log detailed error
        error_log("Error in protect_pdf.php: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        http_response_code(400);
        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];
        
        // Clean up uploaded file if it exists
        if (isset($uploadPath) && file_exists($uploadPath)) {
            unlink($uploadPath);
        }
    }
    
    // Clear any buffered output
    ob_end_clean();
    
    // Set proper JSON header and output
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Password Protection</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-text {
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .upload-area {
            border: 2px dashed #d1d5db;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.05);
        }
        .upload-area.drag-over {
            border-color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-center mb-6">
                <span class="gradient-text">Protect PDF</span> with Password
            </h1>
            
            <form id="pdfProtectForm" enctype="multipart/form-data">
                <!-- PDF Upload -->
                <div id="upload-area" class="upload-area rounded-lg p-8 text-center cursor-pointer mb-6"
                     ondragover="event.preventDefault(); this.classList.add('drag-over')"
                     ondragleave="this.classList.remove('drag-over')"
                     ondrop="event.preventDefault(); this.classList.remove('drag-over'); handleDrop(event)">
                    <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-file-pdf text-2xl text-blue-500"></i>
                    </div>
                    <h3 class="text-lg font-bold mb-2">Select PDF File</h3>
                    <p class="text-gray-500 mb-4">or drag and drop PDF here</p>
                    <input type="file" id="pdfFile" name="pdfFile" accept=".pdf" class="hidden" required>
                    <button type="button" id="browse-btn" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-folder-open mr-2"></i> Browse Files
                    </button>
                    <p class="text-xs text-gray-400 mt-3">PDF files up to 50MB</p>
                    <p id="selected-file" class="mt-2 text-sm font-medium hidden"></p>
                </div>
                
                <!-- Password Input -->
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Set Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                               required>
                        <button type="button" id="togglePassword" class="absolute right-3 top-2 text-gray-500">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" id="protect-btn" class="w-full bg-green-500 hover:bg-green-600 text-white py-3 rounded-lg font-medium">
                    <i class="fas fa-lock mr-2"></i> Protect PDF
                </button>
            </form>
            
            <!-- Result Container -->
            <div id="result-container" class="hidden mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3 text-2xl"></i>
                    <div>
                        <h3 class="font-bold text-green-800">PDF Protected Successfully!</h3>
                        <p id="success-message" class="text-sm text-green-600"></p>
                    </div>
                </div>
            </div>
            
            <!-- Error Container -->
            <div id="error-container" class="hidden mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-times-circle text-red-500 mr-3 text-2xl"></i>
                    <div>
                        <h3 class="font-bold text-red-800">Error</h3>
                        <p id="error-message" class="text-sm text-red-600"></p>
                    </div>
                </div>
            </div>
            
            <!-- Progress Container -->
            <div id="progress-container" class="hidden mt-6">
                <div class="mb-2 flex justify-between">
                    <span>Protecting PDF...</span>
                    <span id="progressPercent">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div id="progressBar" class="bg-blue-500 h-2.5 rounded-full" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const form = document.getElementById('pdfProtectForm');
        const fileInput = document.getElementById('pdfFile');
        const browseBtn = document.getElementById('browse-btn');
        const uploadArea = document.getElementById('upload-area');
        const selectedFile = document.getElementById('selected-file');
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');
        const protectBtn = document.getElementById('protect-btn');
        const resultContainer = document.getElementById('result-container');
        const errorContainer = document.getElementById('error-container');
        const progressContainer = document.getElementById('progress-container');
        const successMessage = document.getElementById('success-message');
        const errorMessage = document.getElementById('error-message');

        // Toggle password visibility
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            togglePassword.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        // Handle file selection
        browseBtn.addEventListener('click', () => fileInput.click());
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                selectedFile.textContent = `Selected: ${file.name}`;
                selectedFile.classList.remove('hidden');
            }
        });
        
        // Handle dropped files
        function handleDrop(event) {
            event.preventDefault();
            uploadArea.classList.remove('drag-over');
            
            const files = event.dataTransfer.files;
            if (files.length > 0 && files[0].type === 'application/pdf') {
                fileInput.files = files;
                selectedFile.textContent = `Selected: ${files[0].name}`;
                selectedFile.classList.remove('hidden');
            }
        }

        // Form submission
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Validate form
            if (!fileInput.files.length) {
                showError('Please select a PDF file');
                return;
            }
            
            if (!passwordInput.value) {
                showError('Password is required');
                return;
            }
            
            // Hide previous messages
            resultContainer.classList.add('hidden');
            errorContainer.classList.add('hidden');
            
            // Show progress
            progressContainer.classList.remove('hidden');
            simulateProgress();
            
            // Prepare form data
            const formData = new FormData(form);
            
            // Submit via AJAX
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Raw response:', text);
                        throw new Error(text || 'Server error: No response body');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showSuccess(data);
                } else {
                    throw new Error(data.message || 'Unknown error occurred');
                }
            })
            .catch(error => {
                showError(error.message || 'An error occurred. Please try again.');
                console.error('Error:', error);
            })
            .finally(() => {
                progressContainer.classList.add('hidden');
            });
        });
        
        function simulateProgress() {
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress >= 90) {
                    clearInterval(interval);
                    return;
                }
                document.getElementById('progressBar').style.width = `${progress}%`;
                document.getElementById('progressPercent').textContent = `${Math.round(progress)}%`;
            }, 300);
        }
        
        function showSuccess(data) {
            successMessage.textContent = data.message;
            resultContainer.classList.remove('hidden');
            form.reset();
            selectedFile.classList.add('hidden');
            // Automatically trigger download
            window.location.href = data.downloadUrl;
        }
        
        function showError(message) {
            errorMessage.textContent = message;
            errorContainer.classList.remove('hidden');
        }
    </script>
</body>
</html>