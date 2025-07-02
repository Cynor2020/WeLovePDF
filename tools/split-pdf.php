<?php
require_once('lib/fpdi/autoload.php');

use setasign\Fpdi\Fpdi;

class PdfSplitter {
    private $inputFile;
    private $outputDirectory;
    
    public function __construct($inputFile, $outputDirectory) {
        if (!file_exists($inputFile)) {
            throw new Exception("Input file does not exist: " . $inputFile);
        }
        
        $this->inputFile = $inputFile;
        $this->outputDirectory = rtrim($outputDirectory, '/') . '/';
        
        if (!file_exists($this->outputDirectory)) {
            if (!mkdir($this->outputDirectory, 0777, true)) {
                throw new Exception("Failed to create output directory: " . $this->outputDirectory);
            }
        }
    }
    
    public function splitByPages($pagesPerFile = 1) {
        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($this->inputFile);
            $files = [];
            
            if ($pageCount === false) {
                throw new Exception("Failed to read PDF file or file is not valid PDF");
            }
            
            for ($i = 1; $i <= $pageCount; $i += $pagesPerFile) {
                $newPdf = new Fpdi();
                $newPdf->setSourceFile($this->inputFile);
                
                $endPage = min($i + $pagesPerFile - 1, $pageCount);
                
                for ($page = $i; $page <= $endPage; $page++) {
                    $templateId = $newPdf->importPage($page);
                    $size = $newPdf->getTemplateSize($templateId);
                    
                    if ($size === false) {
                        throw new Exception("Failed to get page size for page $page");
                    }
                    
                    $orientation = is_array($size) ? $size['orientation'] : $size->getOrientation();
                    $width = is_array($size) ? $size['width'] : $size->getWidth();
                    $height = is_array($size) ? $size['height'] : $size->getHeight();
                    
                    $newPdf->AddPage($orientation, [$width, $height]);
                    $newPdf->useTemplate($templateId);
                }
                
                $outputFile = $this->outputDirectory . 'page_' . $i . '_to_' . $endPage . '.pdf';
                $result = $newPdf->Output($outputFile, 'F');
                
                if ($result === false) {
                    throw new Exception("Failed to save output file: " . $outputFile);
                }
                
                $files[] = $outputFile;
            }
            
            return [
                'pageCount' => $pageCount,
                'files' => $files
            ];
        } catch (Exception $e) {
            throw new Exception("PDF split failed: " . $e->getMessage());
        }
    }
    
    public function splitByRange($ranges) {
        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($this->inputFile);
            $files = [];
            
            if ($pageCount === false) {
                throw new Exception("Failed to read PDF file or file is not valid PDF");
            }
            
            foreach ($ranges as $range) {
                if (!isset($range['start']) || !isset($range['end'])) {
                    continue;
                }
                
                $start = max(1, (int)$range['start']);
                $end = min((int)$range['end'], $pageCount);
                
                if ($start > $end) continue;
                
                $newPdf = new Fpdi();
                $newPdf->setSourceFile($this->inputFile);
                
                for ($page = $start; $page <= $end; $page++) {
                    $templateId = $newPdf->importPage($page);
                    $size = $newPdf->getTemplateSize($templateId);
                    
                    if ($size === false) {
                        throw new Exception("Failed to get page size for page $page");
                    }
                    
                    $orientation = is_array($size) ? $size['orientation'] : $size->getOrientation();
                    $width = is_array($size) ? $size['width'] : $size->getWidth();
                    $height = is_array($size) ? $size['height'] : $size->getHeight();
                    
                    $newPdf->AddPage($orientation, [$width, $height]);
                    $newPdf->useTemplate($templateId);
                }
                
                $outputFile = $this->outputDirectory . 'pages_' . $start . '_to_' . $end . '.pdf';
                $result = $newPdf->Output($outputFile, 'F');
                
                if ($result === false) {
                    throw new Exception("Failed to save output file: " . $outputFile);
                }
                
                $files[] = $outputFile;
            }
            
            return [
                'filesCreated' => count($files),
                'files' => $files
            ];
        } catch (Exception $e) {
            throw new Exception("PDF range split failed: " . $e->getMessage());
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        // Check if file was uploaded
        if (!isset($_FILES['pdfFile']) || $_FILES['pdfFile']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Please upload a valid PDF file");
        }
        
        // Validate file type
        $fileType = mime_content_type($_FILES['pdfFile']['tmp_name']);
        if ($fileType !== 'application/pdf') {
            throw new Exception("Uploaded file is not a PDF");
        }
        
        // Set up directories
        $uploadDir = 'uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $outputDir = isset($_POST['outputDir']) && !empty($_POST['outputDir']) ? $_POST['outputDir'] : 'output/';
        
        // Move uploaded file
        $inputFile = $uploadDir . basename($_FILES['pdfFile']['name']);
        move_uploaded_file($_FILES['pdfFile']['tmp_name'], $inputFile);
        
        $splitter = new PdfSplitter($inputFile, $outputDir);
        
        if ($_POST['action'] === 'splitByPages') {
            $pagesPerFile = isset($_POST['pagesPerFile']) ? (int)$_POST['pagesPerFile'] : 1;
            $result = $splitter->splitByPages($pagesPerFile);
            
            echo json_encode([
                'message' => "Successfully split {$result['pageCount']} pages into " . count($result['files']) . " files.",
                'files' => $result['files']
            ]);
        } elseif ($_POST['action'] === 'splitByRange') {
            if (!isset($_POST['ranges']) || empty($_POST['ranges'])) {
                throw new Exception("Please specify at least one page range");
            }
            
            $result = $splitter->splitByRange($_POST['ranges']);
            
            echo json_encode([
                'message' => "Successfully created {$result['filesCreated']} PDF files from specified ranges.",
                'files' => $result['files']
            ]);
        } else {
            throw new Exception("Invalid action");
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    
    exit;
}
?>