<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeLovePDF - Free Online PDF Tools</title>
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
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <!-- Header -->
    <header class="bg-white shadow-sm fixed w-full z-50">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <!-- Logo -->
                <a href="index.php" class="flex items-center">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center mr-3">
                        <i class="fas fa-file-pdf text-white text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold gradient-text">WeLovePDF</span>
                </a>
                
                <!-- Desktop Navigation -->
                <nav class="hidden lg:flex space-x-8 items-center">
                    <a href="#" class="font-medium hover:text-blue-500 transition">Home</a>
                    <a href="#tools" class="font-medium hover:text-blue-500 transition">Tools</a>
                    <a href="#features" class="font-medium hover:text-blue-500 transition">Features</a>
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
                        <li><a href="#" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition">Home</a></li>
                        <li><a href="#tools" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition">All Tools</a></li>
                        <li><a href="#features" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition">Features</a></li>
                    </ul>
                </div>
                
                <!-- Convert to PDF -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold mb-3 text-blue-500">Convert to PDF</h3>
                    <ul class="space-y-2">
                        <li><a href="./tools/jpg_to_pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-image text-blue-500 mr-2"></i> JPG to PDF</a></li>
                        <li><a href="./tools/word-to-pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-word text-blue-500 mr-2"></i> Word to PDF</a></li>
                        <li><a href="./tools/ppt_to_pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-powerpoint text-blue-500 mr-2"></i> PPT to PDF</a></li>
                        <li><a href="./tools/excel-to-pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-excel text-blue-500 mr-2"></i> Excel to PDF</a></li>
                        <li><a href="./tools/txt-to-pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-alt text-blue-500 mr-2"></i> TXT to PDF</a></li>
                    </ul>
                </div>
                
                <!-- Convert from PDF -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold mb-3 text-blue-500">Convert from PDF</h3>
                    <ul class="space-y-2">
                        <li><a href="./tools/pdf_to_jpg.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-image text-blue-500 mr-2"></i> PDF to JPG</a></li>
                        <li><a href="./tools/pdf_to_word.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-word text-blue-500 mr-2"></i> PDF to Word</a></li>
                        <li><a href="./tools/pdf_to_ppt.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-powerpoint text-blue-500 mr-2"></i> PDF to PPT</a></li>
                        <li><a href="./tools/pdf_to_excel.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-excel text-blue-500 mr-2"></i> PDF to Excel</a></li>
                    </ul>
                </div>
                
                <!-- Edit & Other Tools -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold mb-3 text-blue-500">Edit & Other Tools</h3>
                    <ul class="space-y-2">
                        <li><a href="./tools/merge-pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-object-group text-blue-500 mr-2"></i> Merge PDF</a></li>
                        <li><a href="./tools/split-pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-cut text-blue-500 mr-2"></i> Split PDF</a></li>
                        <li><a href="./tools/compress_pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-compress-alt text-blue-500 mr-2"></i> Compress PDF</a></li>
                        <li><a href="./tools/protect_pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-lock text-blue-500 mr-2"></i> Protect PDF</a></li>
                        <li><a href="./tools/unlock_pdf.php" class="block px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-unlock text-blue-500 mr-2"></i> Unlock PDF</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="pt-32 pb-20">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight">
                    The <span class="gradient-text">Complete PDF Toolkit</span> You Need
                </h1>
                <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
                    Convert, edit, merge, split and compress PDF files with our powerful online tools. 
                    Free to use with no registration required!
                </p>
                
                <!-- Upload Box -->
                <div class="bg-white rounded-xl shadow-md p-6 max-w-2xl mx-auto">
                    <div class="upload-area rounded-lg p-8 text-center cursor-pointer transition">
                        <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-cloud-upload-alt text-3xl text-blue-500"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Select PDF files</h3>
                        <p class="text-gray-500 mb-6">or drag and drop PDFs here</p>
                        <button class="btn-primary text-white px-8 py-3 rounded-lg font-medium inline-flex items-center">
                            <i class="fas fa-folder-open mr-2"></i> Browse Files
                        </button>
                        <p class="text-sm text-gray-400 mt-4">PDFs up to 50MB</p>
                    </div>
                </div>
                
                <div class="mt-12 flex flex-wrap justify-center gap-4">
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i> No watermarks
                    </div>
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i> No registration
                    </div>
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i> Secure processing
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Tools Section -->
    <section id="tools" class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Popular PDF Tools</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Quickly access our most commonly used tools
                </p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- JPG to PDF -->
                <a href="./tools/jpg_to_pdf.php" class="tool-card bg-white p-6 rounded-xl">
                    <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center mb-4">
                        <i class="fas fa-image text-blue-500 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold mb-2">JPG to PDF</h3>
                    <p class="text-gray-500 text-sm mb-4">Convert images to PDF documents</p>
                    <div class="btn-primary text-white px-4 py-2 rounded-lg text-sm inline-block">
                        Convert Now
                    </div>
                </a>
                
                <!-- Word to PDF -->
                <a href="./tools/word-to-pdf.php" class="tool-card bg-white p-6 rounded-xl">
                    <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center mb-4">
                        <i class="fas fa-file-word text-blue-500 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold mb-2">Word to PDF</h3>
                    <p class="text-gray-500 text-sm mb-4">Convert DOC/DOCX files to PDF</p>
                    <div class="btn-primary text-white px-4 py-2 rounded-lg text-sm inline-block">
                        Convert Now
                    </div>
                </a>
                
                <!-- Merge PDF -->
                <a href="./tools/merge-pdf.php" class="tool-card bg-white p-6 rounded-xl">
                    <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center mb-4">
                        <i class="fas fa-object-group text-blue-500 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold mb-2">Merge PDF</h3>
                    <p class="text-gray-500 text-sm mb-4">Combine multiple PDFs into one</p>
                    <div class="btn-primary text-white px-4 py-2 rounded-lg text-sm inline-block">
                        Merge Now
                    </div>
                </a>
                
                <!-- Compress PDF -->
                <a href="./tools/compress-pdf.php" class="tool-card bg-white p-6 rounded-xl">
                    <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center mb-4">
                        <i class="fas fa-compress-alt text-blue-500 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold mb-2">Compress PDF</h3>
                    <p class="text-gray-500 text-sm mb-4">Reduce PDF file size</p>
                    <div class="btn-primary text-white px-4 py-2 rounded-lg text-sm inline-block">
                        Compress Now
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- All Tools Section -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">All PDF Tools</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Everything you need to work with PDF files
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Convert to PDF -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4 text-blue-500">Convert to PDF</h3>
                    <ul class="space-y-2">
                        <li><a href="./tools/jpg_to_pdf.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-image text-blue-500 mr-3 w-5 text-center"></i> JPG to PDF</a></li>
                        <li><a href="./tools/word-to-pdf.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-word text-blue-500 mr-3 w-5 text-center"></i> Word to PDF</a></li>
                        <li><a href="./tools/ppt_to_pdf.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-powerpoint text-blue-500 mr-3 w-5 text-center"></i> PPT to PDF</a></li>
                        <li><a href="./tools/excel-to-pdf.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-excel text-blue-500 mr-3 w-5 text-center"></i> Excel to PDF</a></li>
                        <li><a href="./tools/txt-to-pdf.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-alt text-blue-500 mr-3 w-5 text-center"></i> TXT to PDF</a></li>
                    </ul>
                </div>
                
                <!-- Convert from PDF -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4 text-blue-500">Convert from PDF</h3>
                    <ul class="space-y-2">
                        <li><a href="./tools/pdf_to_jpg.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-image text-blue-500 mr-3 w-5 text-center"></i> PDF to JPG</a></li>
                        <li><a href="./tools/pdf_to_word.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-word text-blue-500 mr-3 w-5 text-center"></i> PDF to Word</a></li>
                        <li><a href="./tools/pdf_to_ppt.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-powerpoint text-blue-500 mr-3 w-5 text-center"></i> PDF to PPT</a></li>
                        <li><a href="./tools/pdf_to_excel.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-file-excel text-blue-500 mr-3 w-5 text-center"></i> PDF to Excel</a></li>
                    </ul>
                </div>
                
                <!-- Edit & Other Tools -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4 text-blue-500">Edit & Other Tools</h3>
                    <ul class="space-y-2">
                        <li><a href="./tools/merge-pdf.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-object-group text-blue-500 mr-3 w-5 text-center"></i> Merge PDF</a></li>
                        <li><a href="./tools/split-pdf.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-cut text-blue-500 mr-3 w-5 text-center"></i> Split PDF</a></li>
                        <li><a href="./tools/compress_pdf.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-compress-alt text-blue-500 mr-3 w-5 text-center"></i> Compress PDF</a></li>
                        <li><a href="./tools/protect_pdf.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-lock text-blue-500 mr-3 w-5 text-center"></i> Protect PDF</a></li>
                        <li><a href="./tools/unlock_pdf.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-unlock text-blue-500 mr-3 w-5 text-center"></i> Unlock PDF</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Why Choose WeLovePDF?</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    The best free online PDF tools with no compromises
                </p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-6 max-w-6xl mx-auto">
                <!-- Feature 1 -->
                <div class="feature-card bg-white rounded-xl p-6">
                    <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center mb-4">
                        <i class="fas fa-dollar-sign text-blue-500 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">100% Free Forever</h3>
                    <p class="text-gray-500">
                        All our tools are completely free with no hidden charges. No watermarks, no registration required.
                    </p>
                </div>
                
                <!-- Feature 2 -->
                <div class="feature-card bg-white rounded-xl p-6">
                    <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center mb-4">
                        <i class="fas fa-shield-alt text-blue-500 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Secure Processing</h3>
                    <p class="text-gray-500">
                        Your files are protected with 256-bit SSL encryption and automatically deleted after processing.
                    </p>
                </div>
                
                <!-- Feature 3 -->
                <div class="feature-card bg-white rounded-xl p-6">
                    <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center mb-4">
                        <i class="fas fa-bolt text-blue-500 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Lightning Fast</h3>
                    <p class="text-gray-500">
                        Our cloud-based infrastructure processes your files in seconds, no matter how large they are.
                    </p>
                </div>
            </div>
        </div>
    </section>

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
                        <li><a href="./tools/merge-pdf.php" class="text-gray-500 hover:text-blue-500 transition">Merge PDF</a></li>
                        <li><a href="./tools/word-to-pdf.php" class="text-gray-500 hover:text-blue-500 transition">Word to PDF</a></li>
                        <li><a href="./tools/compress_pdf.php" class="text-gray-500 hover:text-blue-500 transition">Compress PDF</a></li>
                        <li><a href="./tools/jpg_to_pdf.php" class="text-gray-500 hover:text-blue-500 transition">JPG to PDF</a></li>
                    </ul>
                </div>
                
                <!-- Column 3 -->
                <div>
                    <h4 class="text-lg font-bold mb-4">Company</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-500 hover:text-blue-500 transition">About Us</a></li>
                        <li><a href="#" class="text-gray-500 hover:text-blue-500 transition">Blog</a></li>
                        <li><a href="#" class="text-gray-500 hover:text-blue-500 transition">Contact</a></li>
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
                <p>&copy; 2023 WeLovePDF. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="back-to-top" class="fixed bottom-8 right-8 bg-blue-500 text-white w-12 h-12 rounded-full shadow-lg flex items-center justify-center opacity-0 invisible transition-all duration-300">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- JavaScript -->
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
    </script>
</body>
</html>