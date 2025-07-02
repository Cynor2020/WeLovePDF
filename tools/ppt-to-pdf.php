<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/lib/fpdf.php';
require_once __DIR__ . '/lib/fpdi/autoload.php';

use setasign\Fpdi\Fpdi;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Shape\Drawing;
use PhpOffice\PhpPresentation\Shape\RichText;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Bullet;
use PhpOffice\PhpPresentation\Style\Color;

// Configure upload directory
$uploadDir = __DIR__ . '/../../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pptFile'])) {
    $pptFile = $_FILES['pptFile'];
    $fileName = basename($pptFile['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $tempPath = $pptFile['tmp_name'];
    
    // Validate file type and size (max 50MB)
    $allowedTypes = ['ppt', 'pptx'];
    $maxFileSize = 50 * 1024 * 1024;
    
    if (!in_array($fileExt, $allowedTypes)) {
        die(json_encode(['success' => false, 'message' => 'Only PPT/PPTX files allowed']));
    }
    
    if ($pptFile['size'] > $maxFileSize) {
        die(json_encode(['success' => false, 'message' => 'File size exceeds 50MB limit']));
    }

    try {
        // Load PowerPoint file
        $ppt = IOFactory::load($tempPath);
        
        // Create PDF with improved settings
        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        
        // Process each slide
        foreach ($ppt->getAllSlides() as $slide) {
            // Get slide dimensions (convert points to mm)
            $width = $slide->getExtent()->getDx() / 36000 * 25.4;
            $height = $slide->getExtent()->getDy() / 36000 * 25.4;
            
            // Add slide page with exact dimensions
            $pdf->AddPage($width > $height ? 'L' : 'P', array($width, $height));
            
            // Set slide background
            $bgColor = $slide->getBackground()->getColor();
            if ($bgColor) {
                $rgb = $bgColor->getRGB();
                $pdf->SetFillColor(
                    hexdec(substr($rgb, 0, 2)),
                    hexdec(substr($rgb, 2, 2)),
                    hexdec(substr($rgb, 4, 2))
                );
                $pdf->Rect(0, 0, $width, $height, 'F');
            }
            
            // Process all shapes in slide
            foreach ($slide->getShapeCollection() as $shape) {
                // Handle images
                if ($shape instanceof Drawing) {
                    $imgPath = $uploadDir . 'temp_img_' . uniqid() . '.' . $shape->getExtension();
                    file_put_contents($imgPath, $shape->getContents());
                    
                    // Calculate position and size (convert EMU to mm)
                    $x = $shape->getOffsetX() / 36000 * 25.4;
                    $y = $shape->getOffsetY() / 36000 * 25.4;
                    $w = $shape->getWidth() / 36000 * 25.4;
                    $h = $shape->getHeight() / 36000 * 25.4;
                    
                    $pdf->Image($imgPath, $x, $y, $w, $h);
                    unlink($imgPath);
                } 
                // Handle text elements
                elseif ($shape instanceof RichText) {
                    $text = '';
                    $font = $shape->getFont();
                    $paragraphs = $shape->getParagraphs();
                    
                    // Set text color
                    if ($font->getColor() instanceof Color) {
                        $rgb = $font->getColor()->getRGB();
                        $pdf->SetTextColor(
                            hexdec(substr($rgb, 0, 2)),
                            hexdec(substr($rgb, 2, 2)),
                            hexdec(substr($rgb, 4, 2))
                        );
                    }
                    
                    // Set font style
                    $style = '';
                    if ($font->isBold()) $style .= 'B';
                    if ($font->isItalic()) $style .= 'I';
                    if ($font->isUnderlined()) $style .= 'U';
                    
                    $pdf->SetFont('Arial', $style, $font->getSize() ?: 12);
                    
                    // Calculate position (convert EMU to mm)
                    $x = $shape->getOffsetX() / 36000 * 25.4;
                    $y = $shape->getOffsetY() / 36000 * 25.4;
                    $w = $shape->getWidth() / 36000 * 25.4;
                    
                    $pdf->SetXY($x, $y);
                    
                    // Process each paragraph
                    foreach ($paragraphs as $paragraph) {
                        $align = 'L';
                        switch ($paragraph->getAlignment()->getHorizontal()) {
                            case Alignment::HORIZONTAL_CENTER: $align = 'C'; break;
                            case Alignment::HORIZONTAL_RIGHT: $align = 'R'; break;
                        }
                        
                        // Process bullet points
                        if ($paragraph->getBulletStyle() != Bullet::STYLE_NONE) {
                            $text = "• " . $paragraph->getPlainText();
                        } else {
                            $text = $paragraph->getPlainText();
                        }
                        
                        $pdf->MultiCell($w, $font->getSize()/2, $text, 0, $align);
                        $pdf->Ln(5);
                    }
                }
            }
        }
        
        // Generate output filename
        $outputFilename = 'converted_' . pathinfo($fileName, PATHINFO_FILENAME) . '.pdf';
        $outputPath = $uploadDir . $outputFilename;
        
        // Save PDF
        $pdf->Output('F', $outputPath);
        
        // Verify PDF was created
        if (!file_exists($outputPath)) {
            throw new Exception('Failed to create PDF file');
        }

        // Output PDF to browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $outputFilename . '"');
        header('Content-Length: ' . filesize($outputPath));
        readfile($outputPath);
        
        // Clean up
        unlink($outputPath);
        exit;
        
    } catch (Exception $e) {
        error_log('PPT to PDF Error: ' . $e->getMessage());
        http_response_code(500);
        die(json_encode([
            'success' => false,
            'message' => 'Conversion failed: ' . $e->getMessage()
        ]));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional PPT to PDF Converter</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .dropzone {
            border: 3px dashed #3b82f6;
            transition: all 0.3s;
        }
        .dropzone-active {
            border-color: #10b981;
            background-color: #f0fdf4;
        }
        .convert-btn {
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            transition: all 0.3s;
        }
        .convert-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .slide-preview {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-12 max-w-4xl">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Professional PPT to PDF Converter</h1>
            <p class="text-xl text-gray-600">Convert PowerPoint slides to PDF with perfect layout preservation</p>
        </div>
        
        <div class="bg-white rounded-xl shadow-xl overflow-hidden">
            <div class="p-8">
                <form id="converterForm" method="post" enctype="multipart/form-data" class="space-y-6">
                    <div id="dropzone" class="dropzone rounded-lg p-12 text-center cursor-pointer transition-all duration-300">
                        <input type="file" id="pptFile" name="pptFile" accept=".ppt,.pptx" class="hidden">
                        <div class="w-24 h-24 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Upload PowerPoint File</h3>
                        <p class="text-gray-500 mb-6">Drag & drop your .ppt or .pptx file here, or click to browse</p>
                        <button type="button" id="browseBtn" class="px-6 py-3 bg-blue-500 text-white rounded-lg font-medium inline-flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                            Select File
                        </button>
                        <p class="text-sm text-gray-400 mt-4">Supports PowerPoint files up to 50MB</p>
                    </div>
                    
                    <div id="fileInfo" class="hidden bg-gray-50 p-4 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-blue-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <div>
                                    <p id="fileName" class="font-medium text-gray-800"></p>
                                    <p id="fileSize" class="text-sm text-gray-500"></p>
                                </div>
                            </div>
                            <button type="button" id="clearBtn" class="text-red-500 hover:text-red-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div id="previewContainer" class="hidden">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Slide Preview</h3>
                        <div class="slide-preview bg-white p-4 rounded-lg border border-gray-200">
                            <img id="slidePreview" src="" class="mx-auto max-h-64" alt="Slide Preview">
                        </div>
                    </div>
                    
                    <button id="convertBtn" type="submit" class="convert-btn w-full py-4 px-6 text-white rounded-lg font-bold text-lg flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                        </svg>
                        Convert to PDF
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('pptFile');
        const browseBtn = document.getElementById('browseBtn');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const clearBtn = document.getElementById('clearBtn');
        const previewContainer = document.getElementById('previewContainer');
        const slidePreview = document.getElementById('slidePreview');
        const convertBtn = document.getElementById('convertBtn');
        
        // Handle file selection
        browseBtn.addEventListener('click', () => fileInput.click());
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                displayFileInfo(file);
                
                // Simple preview (first slide would require more complex extraction)
                if (file.type.includes('powerpoint') || file.name.endsWith('.ppt') || file.name.endsWith('.pptx')) {
                    slidePreview.src = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHBhdGggZD0iTTkgMTloNk05IDE1aDZNMTUgNXY0bS0zLTMgMy0zIDMgM3Y0TTUgN2gxNGEyIDIgMCAwMTIgMnYxMGEyIDIgMCAwMS0yIDJINWEyIDIgMCAwMS0yLTJWOWEyIDIgMCAwMTItMnoiLz48L3N2Zz4=';
                    previewContainer.classList.remove('hidden');
                }
            }
        });
        
        // Drag and drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropzone.classList.add('dropzone-active');
        }
        
        function unhighlight() {
            dropzone.classList.remove('dropzone-active');
        }
        
        dropzone.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                fileInput.files = files;
                const file = files[0];
                displayFileInfo(file);
                
                // Simple preview
                if (file.type.includes('powerpoint') || file.name.endsWith('.ppt') || file.name.endsWith('.pptx')) {
                    slidePreview.src = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHBhdGggZD0iTTkgMTloNk05IDE1aDZNMTUgNXY0bS0zLTMgMy0zIDMgM3Y0TTUgN2gxNGEyIDIgMCAwMTIgMnYxMGEyIDIgMCAwMS0yIDJINWEyIDIgMCAwMS0yLTJWOWEyIDIgMCAwMTItMnoiLz48L3N2Zz4=';
                    previewContainer.classList.remove('hidden');
                }
            }
        }
        
        // Display file information
        function displayFileInfo(file) {
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileInfo.classList.remove('hidden');
            dropzone.classList.add('hidden');
            convertBtn.disabled = false;
        }
        
        // Clear selected file
        clearBtn.addEventListener('click', () => {
            fileInput.value = '';
            fileInfo.classList.add('hidden');
            previewContainer.classList.add('hidden');
            dropzone.classList.remove('hidden');
            convertBtn.disabled = true;
        });
        
        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>