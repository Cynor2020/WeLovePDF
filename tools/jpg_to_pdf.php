<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set higher limits for processing
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

// Include the Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Handle file upload and conversion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['imageFiles'])) {
        handleFileUpload();
    } elseif (isset($_POST['imageOrder'])) {
        handlePdfConversion();
    }
    exit;
}

function handleFileUpload() {
    header('Content-Type: application/json');
    
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $uploadedFiles = [];
    $maxFileSize = 50 * 1024 * 1024; // 50MB
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    
    foreach ($_FILES['imageFiles']['tmp_name'] as $key => $tmpName) {
        $fileName = basename($_FILES['imageFiles']['name'][$key]);
        $fileSize = $_FILES['imageFiles']['size'][$key];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Validate file type
        if (!in_array($fileExt, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => $fileName . ' is not a valid image file']);
            exit;
        }
        
        // Validate file size
        if ($fileSize > $maxFileSize) {
            echo json_encode(['success' => false, 'message' => $fileName . ' exceeds maximum file size of 50MB']);
            exit;
        }

        $newFileName = uniqid() . '_' . $fileName;
        $uploadPath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($tmpName, $uploadPath)) {
            $uploadedFiles[] = [
                'name' => $fileName,
                'path' => $newFileName,
                'preview' => '../uploads/' . $newFileName
            ];
        } else {
            echo json_encode(['success' => false, 'message' => 'Error uploading ' . $fileName]);
            exit;
        }
    }
    
    echo json_encode(['success' => true, 'files' => $uploadedFiles]);
    exit;
}

function handlePdfConversion() {
    $uploadDir = __DIR__ . '/../uploads/';
    
    try {
        // Initialize FPDI
        $pdf = new \setasign\Fpdi\Fpdi();
        $imageOrder = json_decode($_POST['imageOrder'], true);
        
        foreach ($imageOrder as $fileName) {
            $filePath = $uploadDir . $fileName;
            if (!file_exists($filePath)) {
                throw new Exception('File ' . $fileName . ' not found.');
            }

            // Get image dimensions in pixels
            list($width, $height) = getimagesize($filePath);
            
            // Determine page orientation
            $orientation = $width > $height ? 'L' : 'P';
            
            // Add page with proper orientation
            $pdf->AddPage($orientation, [$width, $height]);
            
            // Add image to fill the entire page (using original dimensions)
            $pdf->Image($filePath, 0, 0, $width, $height);
            
            // Delete the temporary file
            unlink($filePath);
        }

        // Output PDF directly to browser
        $pdf->Output('I', 'converted_images.pdf');
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'PDF generation failed: ' . $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image to PDF Converter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
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
        .image-preview {
            max-width: 100%;
            max-height: 200px;
            object-fit: contain;
            border-radius: 0.5rem;
        }
        .sortable-ghost {
            opacity: 0.4;
            background: #e2e8f0;
        }
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-center mb-6">Image to PDF Converter</h1>
            
            <div id="upload-area" class="upload-area rounded-lg p-8 text-center cursor-pointer"
                 ondragover="event.preventDefault(); this.classList.add('drag-over')"
                 ondragleave="this.classList.remove('drag-over')"
                 ondrop="event.preventDefault(); this.classList.remove('drag-over'); handleDrop(event)">
                <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-images text-2xl text-blue-500"></i>
                </div>
                <h3 class="text-lg font-bold mb-2">Select Images</h3>
                <p class="text-gray-500 mb-4">or drag and drop images here</p>
                <input type="file" id="imageFiles" name="imageFiles[]" accept="image/*" multiple class="hidden">
                <button id="browse-btn" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium">
                    <i class="fas fa-folder-open mr-2"></i> Browse Files
                </button>
                <p class="text-xs text-gray-400 mt-3">Supports JPG, PNG, GIF, BMP, WEBP (Max 50MB each)</p>
            </div>
            
            <div id="preview-section" class="hidden mt-6">
                <h3 class="text-lg font-bold mb-3">Arrange Images</h3>
                <p class="text-gray-600 mb-4">Drag to reorder images</p>
                <div id="image-list" class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6"></div>
                
                <form id="convert-form" method="post">
                    <input type="hidden" id="image-order" name="imageOrder">
                    <button id="convert-btn" type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white py-3 rounded-lg font-medium">
                        <i class="fas fa-file-pdf mr-2"></i> Convert to PDF
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const fileInput = document.getElementById('imageFiles');
        const browseBtn = document.getElementById('browse-btn');
        const uploadArea = document.getElementById('upload-area');
        const previewSection = document.getElementById('preview-section');
        const imageList = document.getElementById('image-list');
        const convertForm = document.getElementById('convert-form');
        const imageOrderInput = document.getElementById('image-order');
        const convertBtn = document.getElementById('convert-btn');

        // Handle browse button click
        browseBtn.addEventListener('click', () => fileInput.click());
        
        // Handle file selection
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                uploadFiles(e.target.files);
            }
        });
        
        // Handle dropped files
        function handleDrop(event) {
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                uploadFiles(files);
            }
        }
        
        // Upload files to server
        function uploadFiles(files) {
            uploadArea.innerHTML = `
                <div class="py-8">
                    <i class="fas fa-circle-notch loading-spinner text-blue-500 text-2xl mb-2"></i>
                    <p class="text-gray-600">Uploading ${files.length} images...</p>
                </div>
            `;
            
            const formData = new FormData();
            Array.from(files).forEach(file => formData.append('imageFiles[]', file));

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showImagePreviews(data.files);
                } else {
                    alert(data.message);
                    resetUploader();
                }
            })
            .catch(error => {
                alert('Error: ' + error);
                resetUploader();
            });
        }
        
        // Display image previews with drag-and-drop reordering
        function showImagePreviews(files) {
            uploadArea.style.display = 'none';
            previewSection.classList.remove('hidden');
            imageList.innerHTML = '';
            
            files.forEach(file => {
                const item = document.createElement('div');
                item.className = 'image-item bg-white p-3 rounded-lg shadow-sm border border-gray-200';
                item.innerHTML = `
                    <div class="flex flex-col items-center">
                        <img src="${file.preview}" class="image-preview mb-3">
                        <p class="text-sm text-center truncate w-full">${file.name}</p>
                        <input type="hidden" name="imageOrder[]" value="${file.path}">
                    </div>
                `;
                imageList.appendChild(item);
            });
            
            // Initialize drag and drop sorting
            new Sortable(imageList, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function() {
                    // Update the hidden inputs to maintain order
                    const items = imageList.querySelectorAll('.image-item');
                    const order = Array.from(items).map(item => 
                        item.querySelector('input').value
                    );
                    imageOrderInput.value = JSON.stringify(order);
                }
            });
        }
        
        // Handle form submission
        convertForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get current order
            const items = imageList.querySelectorAll('.image-item');
            const order = Array.from(items).map(item => 
                item.querySelector('input').value
            );
            imageOrderInput.value = JSON.stringify(order);
            
            convertBtn.disabled = true;
            convertBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Creating PDF...';
            
            // Submit the form
            this.submit();
        });
        
        // Reset the uploader to initial state
        function resetUploader() {
            fileInput.value = '';
            uploadArea.style.display = 'block';
            uploadArea.innerHTML = `
                <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-images text-2xl text-blue-500"></i>
                </div>
                <h3 class="text-lg font-bold mb-2">Select Images</h3>
                <p class="text-gray-500 mb-4">or drag and drop images here</p>
                <button id="browse-btn" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium">
                    <i class="fas fa-folder-open mr-2"></i> Browse Files
                </button>
                <p class="text-xs text-gray-400 mt-3">Supports JPG, PNG, GIF, BMP, WEBP (Max 50MB each)</p>
            `;
            previewSection.classList.add('hidden');
            imageList.innerHTML = '';
            
            // Reattach event listeners
            document.getElementById('browse-btn').addEventListener('click', () => fileInput.click());
            convertBtn.disabled = false;
            convertBtn.innerHTML = '<i class="fas fa-file-pdf mr-2"></i> Convert to PDF';
        }
    </script>
</body>
</html>