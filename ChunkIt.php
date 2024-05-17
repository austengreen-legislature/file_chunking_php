<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $totalChunks = (int)$_POST['totalChunks'];
    $currentChunk = (int)$_POST['currentChunk'];
    $fileName = $_POST['fileName'];
    $tempDir = 'uploads/tmp/';
    $uploadDir = 'uploads/';

    // Ensure the temp directory exists
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    // Create a unique temporary file path for this upload
    $tempFilePath = $tempDir . $fileName . '.part' . $currentChunk;

    // Move the uploaded chunk to the temp directory
    if (move_uploaded_file($_FILES['file']['tmp_name'], $tempFilePath)) {
        // Check if all chunks are uploaded
        if ($currentChunk + 1 === $totalChunks) {
            // All chunks uploaded, assemble the final file
            $finalFilePath = $uploadDir . basename($fileName);
            $finalFile = fopen($finalFilePath, 'wb');

            if ($finalFile) {
                for ($i = 0; $i < $totalChunks; $i++) {
                    $partPath = $tempDir . $fileName . '.part' . $i;
                    $partFile = fopen($partPath, 'rb');

                    if ($partFile) {
                        while ($data = fread($partFile, 1024)) {
                            fwrite($finalFile, $data);
                        }
                        fclose($partFile);
                        // Delete the part file after adding to the final file
                        unlink($partPath);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to open chunk ' . $i . ' for reading.']);
                        exit;
                    }
                }
                fclose($finalFile);
                echo json_encode(['success' => true, 'message' => 'File uploaded successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create final file for writing.']);
            }
        } else {
            // Chunk uploaded successfully, wait for the next chunk
            echo json_encode(['success' => true]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded chunk.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
