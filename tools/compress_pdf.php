<?php
require_once __DIR__ . '/../vendor/autoload.php';

use setasign\Fpdi\Fpdi;

$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdfFile'])) {
    header('Content-Type: application/json');
    
    try {
        $pdfFile = $_FILES['pdfFile'];
        
        // File validation
        if ($pdfFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed with error code: ' . $pdfFile['error']);
        }

        $fileExt = strtolower(pathinfo($pdfFile['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'pdf') {
            throw new Exception('Only PDF files are allowed');
        }

        // Get compression level
        $compressionLevel = $_POST['compressionLevel'] ?? 'medium';
        
        // Temporary file paths
        $tempPdfPath = $uploadDir . uniqid() . '.pdf';
        $outputFile = $uploadDir . uniqid() . '_compressed.pdf';

        // Move uploaded file
        if (!move_uploaded_file($pdfFile['tmp_name'], $tempPdfPath)) {
            throw new Exception('Failed to move uploaded file');
        }

        // Process with FPDI
        $pdf = new Fpdi();
        
        // Set compression based on level
        switch($compressionLevel) {
            case 'high':
                $pdf->setCompression(true);
                $pdf->setImageScale(0.5);
                break;
            case 'medium':
                $pdf->setCompression(true);
                $pdf->setImageScale(0.75);
                break;
            case 'low':
            default:
                $pdf->setCompression(false);
                break;
        }

        $pageCount = $pdf->setSourceFile($tempPdfPath);
        
        for ($i = 1; $i <= $pageCount; $i++) {
            $template = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($template);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($template);
        }

        $pdf->Output('F', $outputFile);
        
        // Return download information
        echo json_encode([
            'success' => true,
            'filename' => 'compressed_'.$pdfFile['name'],
            'filepath' => $outputFile
        ]);
        exit;

    } catch (Exception $e) {
        // Clean up
        if (isset($tempPdfPath) && file_exists($tempPdfPath)) unlink($tempPdfPath);
        if (isset($outputFile) && file_exists($outputFile)) unlink($outputFile);
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Compress PDF</title>
    <script>
        function compressPDF() {
            const formData = new FormData(document.getElementById('compressorForm'));
            const xhr = new XMLHttpRequest();
            
            document.getElementById('compressBtn').disabled = true;
            document.getElementById('progressContainer').style.display = 'block';
            
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    document.getElementById('progressBar').style.width = percent + '%';
                    document.getElementById('progressPercent').textContent = percent + '%';
                }
            });
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    document.getElementById('progressContainer').style.display = 'none';
                    
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            // Create hidden iframe for download
                            const iframe = document.createElement('iframe');
                            iframe.style.display = 'none';
                            iframe.src = response.filepath;
                            iframe.onload = function() {
                                // Trigger download
                                const a = document.createElement('a');
                                a.href = response.filepath;
                                a.download = response.filename;
                                document.body.appendChild(a);
                                a.click();
                                document.body.removeChild(a);
                                setTimeout(() => {
                                    document.body.removeChild(iframe);
                                }, 100);
                            };
                            document.body.appendChild(iframe);
                        } else {
                            alert('Error: ' + response.message);
                        }
                    } catch (e) {
                        alert('Error processing PDF: ' + e.message);
                    }
                    
                    document.getElementById('compressBtn').disabled = false;
                }
            };
            
            xhr.open('POST', '', true);
            xhr.send(formData);
        }
        
        function updateFileInfo() {
            const fileInput = document.getElementById('pdfFile');
            if (fileInput.files.length > 0) {
                document.getElementById('fileName').textContent = fileInput.files[0].name;
                document.getElementById('fileSize').textContent = formatFileSize(fileInput.files[0].size);
                document.getElementById('fileInfo').style.display = 'block';
                document.getElementById('compressBtn').disabled = false;
            }
        }
        
        function clearFile() {
            document.getElementById('pdfFile').value = '';
            document.getElementById('fileInfo').style.display = 'none';
            document.getElementById('compressBtn').disabled = true;
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const units = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(1024));
            return parseFloat((bytes / Math.pow(1024, i)).toFixed(1)) + ' ' + units[i];
        }
    </script>
</head>
<body>
    <h1>Compress PDF</h1>
    
    <form id="compressorForm" enctype="multipart/form-data">
        <div>
            <input type="file" id="pdfFile" name="pdfFile" accept=".pdf" onchange="updateFileInfo()">
        </div>
        
        <div id="fileInfo" style="display:none">
            <p>Selected file: <span id="fileName"></span> (<span id="fileSize"></span>)</p>
            <button type="button" onclick="clearFile()">Remove</button>
        </div>
        
        <h3>Compression Level:</h3>
        <div>
            <input type="radio" name="compressionLevel" value="high" id="highCompression">
            <label for="highCompression">High (smaller size)</label>
        </div>
        <div>
            <input type="radio" name="compressionLevel" value="medium" id="mediumCompression" checked>
            <label for="mediumCompression">Medium</label>
        </div>
        <div>
            <input type="radio" name="compressionLevel" value="low" id="lowCompression">
            <label for="lowCompression">Low (best quality)</label>
        </div>
        
        <div id="progressContainer" style="display:none">
            <p>Compressing... <span id="progressPercent">0%</span></p>
            <div style="width:100%;background:#ddd">
                <div id="progressBar" style="width:0%;height:20px;background:blue"></div>
            </div>
        </div>
        
        <button id="compressBtn" type="button" onclick="compressPDF()" disabled>Compress PDF</button>
    </form>
</body>
</html>