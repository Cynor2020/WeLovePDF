<?php
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);
ini_set('display_errors', 0);

// Handle download request
if (isset($_GET['download']) && isset($_GET['file'])) {
    $outputDir = __DIR__ . '/output/';
    $file = basename($_GET['file']);
    $filePath = $outputDir . $file;
    
    if (file_exists($filePath)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        unlink($filePath);
        exit;
    }
    
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'File not found']);
    exit;
}

// Handle PDF upload and page numbering
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdfFile'])) {
    ob_start();
    
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Server error: Composer autoload not found']);
        exit;
    }
    require_once $autoloadPath;
    
    if (!class_exists('setasign\Fpdi\Fpdi') && !class_exists('setasign\Fpdi\Tcpdf\Fpdi')) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Server error: FPDI class not found']);
        exit;
    }

    $uploadDir = __DIR__ . '/Uploads/';
    $outputDir = __DIR__ . '/output/';
    
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Server error: Cannot create upload directory']);
        exit;
    }
    if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true)) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Server error: Cannot create output directory']);
        exit;
    }

    $pdfFile = $_FILES['pdfFile'];
    if ($pdfFile['error'] !== UPLOAD_ERR_OK) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'File upload error']);
        exit;
    }
    
    $fileExt = strtolower(pathinfo($pdfFile['name'], PATHINFO_EXTENSION));
    if ($fileExt !== 'pdf') {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Only PDF files are allowed']);
        exit;
    }

    $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $pdfFile['name']);
    $uploadPath = $uploadDir . $fileName;
    
    if (!move_uploaded_file($pdfFile['tmp_name'], $uploadPath)) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
        exit;
    }

    try {
        $pdf = class_exists('setasign\Fpdi\Tcpdf\Fpdi') ? new \setasign\Fpdi\Tcpdf\Fpdi() : new \setasign\Fpdi\Fpdi();
        
        // Set document properties
        $pdf->SetCreator('PDF Page Numbering Tool');
        $pdf->SetAuthor('User');
        $pdf->SetTitle('Numbered PDF');
        
        // Set margins - right margin is 5mm for page numbers
        $pdf->SetMargins(15, 15, 5);
        $pdf->SetAutoPageBreak(true, 15);
        
        $pageCount = $pdf->setSourceFile($uploadPath);
        
        if ($pageCount === 0) {
            unlink($uploadPath);
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'The PDF file is empty or corrupted']);
            exit;
        }
        
        for ($i = 1; $i <= $pageCount; $i++) {
            $templateId = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($templateId);
            
            $width = $size['width'] > 0 ? $size['width'] : 210;
            $height = $size['height'] > 0 ? $size['height'] : 297;
            $orientation = $width > $height ? 'L' : 'P';
            
            // Add a new page
            $pdf->AddPage($orientation, [$width, $height]);
            
            // Add the imported page
            $pdf->useTemplate($templateId);
            
            // Add page number at the TOP RIGHT corner with 5mm spacing
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->SetTextColor(0, 0, 0);
            
            $pageText = "Page " . $i;
            $textWidth = $pdf->GetStringWidth($pageText);
            
            // Position exactly 5mm from right and 5mm from top
            $x = $width - $textWidth - 5; // 5mm from right edge
            $y = 5; // 5mm from top
            
            // Make sure we're writing at the absolute top
            $pdf->SetXY($x, $y);
            $pdf->Cell(0, 0, $pageText, 0, 0, 'R');
        }
        
        $outputFilename = 'numbered_' . $fileName;
        $outputPath = $outputDir . $outputFilename;
        $pdf->Output($outputPath, 'F');
        
        unlink($uploadPath);
        
        // Clean up old files
        $files = glob($outputDir . '*.pdf');
        foreach ($files as $file) {
            if (filemtime($file) < time() - 24 * 3600) {
                unlink($file);
            }
        }
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Page numbers added successfully',
            'downloadUrl' => '?download=true&file=' . $outputFilename
        ]);
        exit;
        
    } catch (Exception $e) {
        unlink($uploadPath);
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'PDF is corrupted or password-protected. Please unlock it first.']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Page Numbers to PDF | WeLovePDF</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            scrollbar-width: thin;
            scrollbar-color: #3b82f6 #f1f1f1;
        }
        
        /* Scrollbar styles */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background-color: #3b82f6;
            border-radius: 10px;
            border: 2px solid transparent;
            background-clip: content-box;
        }
        
        .dark ::-webkit-scrollbar-track {
            background: #1f2937;
        }
        
        .dark ::-webkit-scrollbar-thumb {
            background-color: #2563eb;
        }
        
        .gradient-text {
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .tool-card {
            transition: all 0.3s ease;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }
        
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.1);
            border-color: #3b82f6;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
        }
        
        .mobile-menu {
            transform: translateX(-100%);
            transition: transform 0.3s ease-out;
        }
        
        .mobile-menu.open {
            transform: translateX(0);
        }
        
        .mobile-menu-overlay {
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease-out;
        }
        
        .mobile-menu-overlay.open {
            opacity: 1;
            visibility: visible;
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
            background-color: rgba(59, 130, 246, 0.05);
        }
        
        .feature-card {
            transition: all 0.3s ease;
            border-radius: 12px;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.1);
        }

        /* Hide scrollbar when mobile menu is open */
        body.menu-open {
            overflow: hidden;
        }
        
        /* Image preview styles */
        .image-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: contain;
        }
        
        .sortable-ghost {
            opacity: 0.4;
            background: #e2e8f0;
        }
        
        .dark .sortable-ghost {
            background: #4b5563;
        }
        
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        #progressBar {
            transition: width 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <!-- Header -->
    <header class="bg-white shadow-sm fixed w-full z-50">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <!-- Logo -->
                <a href="../index.php" class="flex items-center">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center mr-3">
                        <i class="fas fa-file-pdf text-white text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold gradient-text">WeLovePDF</span>
                </a>
                
                <!-- Desktop Navigation -->
                <nav class="hidden lg:flex space-x-8 items-center">
                    <a href="../index.php" class="font-medium hover:text-blue-500 transition">Home</a>
                    <a href="../tools.php" class="font-medium hover:text-blue-500 transition">Tools</a>
                    <a href="../about.php" class="font-medium hover:text-blue-500 transition">About</a>
                    <a href="../contact.php" class="font-medium hover:text-blue-500 transition">Contact</a>
                    <button id="dark-mode-toggle" class="ml-4 p-2 rounded-full hover:bg-gray-100 transition">
                        <i class="fas fa-moon text-blue-500"></i>
                        <i class="fas fa-sun hidden text-yellow-400"></i>
                    </button>
                </nav>
                
                <!-- Mobile Menu Button -->
                <div class="flex items-center lg:hidden">
                    <button id="mobile-dark-mode-toggle" class="p-2 rounded-full hover:bg-gray-100 transition mr-2">
                        <i class="fas fa-moon text-blue-500"></i>
                        <i class="fas fa-sun hidden text-yellow-400"></i>
                    </button>
                    <button id="mobile-menu-button" class="text-2xl p-2 rounded-full hover:bg-gray-100 transition">
                        <i class="fas fa-bars text-blue-500"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Menu -->
    <div class="lg:hidden">
        <div id="mobile-menu-overlay" class="mobile-menu-overlay fixed inset-0 bg-black bg-opacity-50 z-40"></div>
        <div id="mobile-menu" class="mobile-menu fixed top-0 left-0 h-full w-72 bg-white shadow-xl z-50 overflow-y-auto">
            <div class="px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center mr-3">
                        <i class="fas fa-file-pdf text-white text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold gradient-text">WeLovePDF</span>
                </div>
                <button id="mobile-menu-close" class="text-2xl p-2 rounded-full hover:bg-gray-100 transition">
                    <i class="fas fa-times text-gray-500"></i>
                </button>
            </div>
            
            <div class="px-4 py-6">
                <div class="mb-8">
                    <h3 class="text-lg font-bold mb-4 text-blue-500">MENU</h3>
                    <ul class="space-y-2">
                        <li><a href="../index.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition">Home</a></li>
                        <li><a href="../tools.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition">All Tools</a></li>
                        <li><a href="../about.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition">About</a></li>
                        <li><a href="../contact.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition">Contact</a></li>
                    </ul>
                </div>
                
                <!-- Convert to PDF -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold mb-3 text-blue-500">Convert to PDF</h3>
                    <ul class="space-y-2">
                        <li><a href="../tools/jpg-to-pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-image text-blue-500 mr-2"></i> JPG to PDF</a></li>
                        <li><a href="../tools/word-to-pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-word text-blue-500 mr-2"></i> Word to PDF</a></li>
                        <li><a href="../tools/ppt-to-pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-powerpoint text-blue-500 mr-2"></i> PPT to PDF</a></li>
                        <li><a href="../tools/excel-to-pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-excel text-blue-500 mr-2"></i> Excel to PDF</a></li>
                        <li><a href="../tools/txt-to-pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-alt text-blue-500 mr-2"></i> TXT to PDF</a></li>
                    </ul>
                </div>
                
                <!-- Convert from PDF -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold mb-3 text-blue-500">Convert from PDF</h3>
                    <ul class="space-y-2">
                        <li><a href="../tools/pdf-to-jpg.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-image text-blue-500 mr-2"></i> PDF to JPG</a></li>
                        <li><a href="../tools/pdf-to-word.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-word text-blue-500 mr-2"></i> PDF to Word</a></li>
                        <li><a href="../tools/pdf-to-ppt.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-powerpoint text-blue-500 mr-2"></i> PDF to PPT</a></li>
                        <li><a href="../tools/pdf-to-excel.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-excel text-blue-500 mr-2"></i> PDF to Excel</a></li>
                        <li><a href="../tools/pdf-to-txt.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-alt text-blue-500 mr-2"></i> PDF to TXT</a></li>
                    </ul>
                </div>
                
                <!-- Edit & Other Tools -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold mb-3 text-blue-500">Edit & Other Tools</h3>
                    <ul class="space-y-2">
                        <li><a href="../tools/merge-pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-object-group text-blue-500 mr-2"></i> Merge PDF</a></li>
                        <li><a href="../tools/split-pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-cut text-blue-500 mr-2"></i> Split PDF</a></li>
                        <li><a href="../tools/compress-pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-compress-alt text-blue-500 mr-2"></i> Compress PDF</a></li>
                        <li><a href="../tools/protect-pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-lock text-blue-500 mr-2"></i> Protect PDF</a></li>
                        <li><a href="../tools/unlock-pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-unlock text-blue-500 mr-2"></i> Unlock PDF</a></li>
                        <li><a href="../tools/rotate-pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-redo text-blue-500 mr-2"></i> Rotate PDF</a></li>
                        <li><a href="../tools/organize-pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-sort-numeric-down text-blue-500 mr-2"></i> Organize PDF</a></li>
                        <li><a href="../tools/add-watermark.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-tint text-blue-500 mr-2"></i> Add Watermark</a></li>
                        <li><a href="../tools/add-number.php" class="block px-3 py-2 rounded-lg bg-blue-50 text-blue-600 transition"><i class="fas fa-sort-numeric-up text-blue-500 mr-2"></i> Add Page Numbers</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="pt-32 pb-20 min-h-screen">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl md:text-4xl font-bold mb-6 text-center">
                    Add <span class="gradient-text">Page Numbers to PDF</span>
                </h1>
                <p class="text-lg text-gray-600 mb-8 text-center">
                    Upload your PDF document and we'll add sequential page numbers to each page.
                </p>
                
                <!-- Upload Box -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <form id="converterForm" method="post" enctype="multipart/form-data" class="space-y-6">
                        <div id="uploadContainer" class="upload-area border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer"
                             ondragover="event.preventDefault(); this.classList.add('drag-over')"
                             ondragleave="this.classList.remove('drag-over')"
                             ondrop="event.preventDefault(); this.classList.remove('drag-over'); handleFiles(event.dataTransfer.files)">
                            <input type="file" id="pdfFile" name="pdfFile" accept=".pdf" class="hidden">
                            <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-file-pdf text-3xl text-blue-500"></i>
                            </div>
                            <h3 class="text-xl font-bold mb-2">Select PDF Document</h3>
                            <p class="text-gray-500 mb-4">or drag and drop file here</p>
                            <button type="button" onclick="document.getElementById('pdfFile').click()" 
                                    class="btn-primary text-white px-8 py-3 rounded-lg font-medium inline-flex items-center">
                                <i class="fas fa-folder-open mr-2"></i> Browse Files
                            </button>
                            <p class="text-sm text-gray-400 mt-4">Supports .pdf files up to 50MB</p>
                        </div>
                        
                        <div id="fileInfo" class="hidden">
                            <div class="flex items-center justify-between bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-file-pdf text-2xl text-blue-500 mr-3"></i>
                                    <div>
                                        <p id="fileName" class="font-medium"></p>
                                        <p id="fileSize" class="text-sm text-gray-500"></p>
                                    </div>
                                </div>
                                <button type="button" onclick="clearFile()" class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div id="progressContainer" class="hidden">
                            <div class="mb-2 flex justify-between">
                                <span>Processing...</span>
                                <span id="progressPercent">0%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div id="progressBar" class="bg-blue-500 h-2.5 rounded-full" style="width: 0%"></div>
                            </div>
                        </div>
                        
                        <button id="convertBtn" type="submit" 
                                class="btn-primary text-white px-8 py-3 rounded-lg font-medium w-full disabled:opacity-50 disabled:cursor-not-allowed"
                                disabled>
                            <i class="fas fa-file-pdf mr-2"></i> Add Page Numbers & Download
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-100 py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <!-- Column 1 -->
                <div>
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center mr-3">
                            <i class="fas fa-file-pdf text-white text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold gradient-text">WeLovePDF</span>
                    </div>
                    <p class="mb-4 text-gray-500">
                        The ultimate collection of free online PDF tools for all your document needs.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-blue-500 text-xl transition"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-blue-500 text-xl transition"><i class="fab fa-github"></i></a>
                    </div>
                </div>
                
                <!-- Column 2 -->
                <div>
                    <h4 class="text-lg font-bold mb-4">PDF Tools</h4>
                    <ul class="space-y-2">
                        <li><a href="../tools/merge-pdf.php" class="text-gray-500 hover:text-blue-500 transition">Merge PDF</a></li>
                        <li><a href="../tools/word-to-pdf.php" class="text-gray-500 hover:text-blue-500 transition">Word to PDF</a></li>
                        <li><a href="../tools/compress-pdf.php" class="text-gray-500 hover:text-blue-500 transition">Compress PDF</a></li>
                        <li><a href="../tools/jpg-to-pdf.php" class="text-gray-500 hover:text-blue-500 transition">JPG to PDF</a></li>
                    </ul>
                </div>
                
                <!-- Column 3 -->
                <div>
                    <h4 class="text-lg font-bold mb-4">Company</h4>
                    <ul class="space-y-2">
                        <li><a href="../about.php" class="text-gray-500 hover:text-blue-500 transition">About Us</a></li>
                        <li><a href="#" class="text-gray-500 hover:text-blue-500 transition">Blog</a></li>
                        <li><a href="../contact.php" class="text-gray-500 hover:text-blue-500 transition">Contact</a></li>
                    </ul>
                </div>
                
                <!-- Column 4 -->
                <div>
                    <h4 class="text-lg font-bold mb-4">Legal</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-500 hover:text-blue-500 transition">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-500 hover:text-blue-500 transition">Terms of Service</a></li>
                        <li><a href="#" class="text-gray-500 hover:text-blue-500 transition">Security</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-100 pt-8 text-center text-gray-500">
                <p>&copy; <?php echo date('Y'); ?> WeLovePDF. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="back-to-top" class="fixed bottom-8 right-8 bg-blue-500 text-white w-12 h-12 rounded-full shadow-lg flex items-center justify-center opacity-0 invisible transition-all duration-300">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
        // Mobile Menu Toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenuClose = document.getElementById('mobile-menu-close');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        const mobileDarkModeToggle = document.getElementById('mobile-dark-mode-toggle');
        const darkModeToggle = document.getElementById('dark-mode-toggle');
        
        function toggleMobileMenu() {
            mobileMenu.classList.toggle('open');
            mobileMenuOverlay.classList.toggle('open');
            document.body.classList.toggle('menu-open');
        }
        
        mobileMenuButton.addEventListener('click', toggleMobileMenu);
        mobileMenuClose.addEventListener('click', toggleMobileMenu);
        mobileMenuOverlay.addEventListener('click', toggleMobileMenu);
        
        // Dark Mode Toggle
        function toggleDarkMode() {
            const html = document.documentElement;
            const isDark = html.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark);
            
            // Update all icons
            const moonIcons = document.querySelectorAll('.fa-moon');
            const sunIcons = document.querySelectorAll('.fa-sun');
            
            if (html.classList.contains('dark')) {
                moonIcons.forEach(icon => icon.classList.add('hidden'));
                sunIcons.forEach(icon => icon.classList.remove('hidden'));
            } else {
                moonIcons.forEach(icon => icon.classList.remove('hidden'));
                sunIcons.forEach(icon => icon.classList.add('hidden'));
            }
        }
        
        // Initialize dark mode from localStorage
        function initDarkMode() {
            const darkMode = localStorage.getItem('darkMode');
            
            if (darkMode === 'true') {
                document.documentElement.classList.add('dark');
                document.querySelectorAll('.fa-moon').forEach(icon => icon.classList.add('hidden'));
                document.querySelectorAll('.fa-sun').forEach(icon => icon.classList.remove('hidden'));
            }
        }
        
        // Event listeners
        darkModeToggle.addEventListener('click', toggleDarkMode);
        if (mobileDarkModeToggle) {
            mobileDarkModeToggle.addEventListener('click', toggleDarkMode);
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', initDarkMode);
        
        // Back to Top Button
        const backToTopButton = document.getElementById('back-to-top');
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.remove('opacity-0', 'invisible');
                backToTopButton.classList.add('opacity-100', 'visible');
            } else {
                backToTopButton.classList.remove('opacity-100', 'visible');
                backToTopButton.classList.add('opacity-0', 'invisible');
            }
        });
        
        backToTopButton.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                if(this.getAttribute('href') === '#') {
                    e.preventDefault();
                    return;
                }
                
                // Allow normal navigation for PHP links
                if(this.getAttribute('href').endsWith('.php')) {
                    return;
                }
                
                // Only smooth scroll for anchor links
                e.preventDefault();
                const targetElement = document.querySelector(this.getAttribute('href'));
                if(targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                    
                    // Close mobile menu if open
                    if (mobileMenu.classList.contains('open')) {
                        toggleMobileMenu();
                    }
                }
            });
        });
        
        // PDF Numbering Functionality
        const pdfFileInput = document.getElementById('pdfFile');
        const uploadContainer = document.getElementById('uploadContainer');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const convertBtn = document.getElementById('convertBtn');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const progressPercent = document.getElementById('progressPercent');
        const form = document.getElementById('converterForm');
        
        // Handle file selection
        pdfFileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                displayFileInfo(this.files[0]);
            }
        });
        
        // Handle dropped files
        function handleFiles(files) {
            if (files.length > 0) {
                pdfFileInput.files = files;
                displayFileInfo(files[0]);
            }
        }
        
        // Display file information
        function displayFileInfo(file) {
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileInfo.classList.remove('hidden');
            uploadContainer.classList.add('hidden');
            convertBtn.disabled = false;
        }
        
        // Clear selected file
        function clearFile() {
            pdfFileInput.value = '';
            fileInfo.classList.add('hidden');
            uploadContainer.classList.remove('hidden');
            convertBtn.disabled = true;
        }
        
        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Form submission with progress indication
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();
            
            // Show progress
            convertBtn.disabled = true;
            progressContainer.classList.remove('hidden');
            
            // Progress handler
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percentComplete + '%';
                    progressPercent.textContent = percentComplete + '%';
                }
            });
            
            // Completion handler
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                // Handle download
                                window.location.href = response.downloadUrl;
                            } else {
                                alert('Error: ' + response.message);
                            }
                        } catch {
                            alert('An error occurred during processing');
                        }
                    } else {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            alert('Error: ' + response.message);
                        } catch {
                            alert('An error occurred during processing');
                        }
                    }
                    
                    // Reset form
                    progressContainer.classList.add('hidden');
                    clearFile();
                }
            };
            
            xhr.open('POST', '', true);
            xhr.send(formData);
        });
    </script>
</body>
</html>