<?php
if (isset($_GET['file'])) {
    $file = __DIR__ . '/uploads/' . basename($_GET['file']);
    if (file_exists($file)) {
        // Serve the PDF file
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        readfile($file);
        unlink($file); // Delete the file after serving
        // Output JavaScript for redirect after download
        echo '<script>window.location.href = "../tools/jpg-to-pdf.php";</script>';
        exit;
    } else {
        http_response_code(404);
        echo 'File not found.';
    }
} else {
    http_response_code(400);
    echo 'No file specified.';
}
?>