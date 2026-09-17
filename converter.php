<?php

class PharConverter
{
    /**
     * Converts a PHAR file to ZIP format.
     *
     * @param string $inputFile The input PHAR file.
     * @throws PharException If an error occurs during the conversion.
     */
    private function convertPharToZip(string $inputFile): void
    {
        $phar = new Phar($inputFile);
        $phar->convertToData(Phar::ZIP);
        echo "PHAR file converted to ZIP format.\n";
    }

    /**
     * Converts a ZIP file to PHAR format.
     *
     * @param string $inputFile The input ZIP file.
     * @param string $outputFile The output PHAR file.
     * @throws PharException If an error occurs during the conversion.
     */
    private function convertZipToPhar(string $inputFile, string $outputFile): void
    {
        $outputPharFile = substr($outputFile, 0, -4) . '.phar';

        $phar = new Phar($outputPharFile);

        // Extract the contents of the ZIP file to a temporary directory
        $tempDir = sys_get_temp_dir() . '/phar_converter_temp';
        if (!file_exists($tempDir)) {
            mkdir($tempDir);
        }
        $zip = new ZipArchive();
        if ($zip->open($inputFile) === true) {
            $zip->extractTo($tempDir);
            $zip->close();
        }

        // Build the PHAR from the extracted contents
        $phar->buildFromDirectory($tempDir);

        // Clean up the temporary directory
        $this->removeDirectory($tempDir);

        echo "ZIP file converted to PHAR format.\n";
    }

    /**
     * Removes a directory and its contents recursively.
     *
     * @param string $dir The directory to remove.
     */
    private function removeDirectory(string $dir): void
    {
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }

    /**
     * Converts between PHAR and ZIP formats based on input and output file extensions.
     *
     * @param string $inputFile The input file.
     * @param string $outputFile The output file.
     * @throws PharException If an error occurs during the conversion.
     */
    public function __construct(string $inputFile, string $outputFile)
    {
        $inputExtension = pathinfo($inputFile, PATHINFO_EXTENSION);
        $outputExtension = pathinfo($outputFile, PATHINFO_EXTENSION);

        if ($inputExtension === 'phar' && $outputExtension === 'zip') {
            $this->convertPharToZip($inputFile);
        } elseif ($inputExtension === 'zip' && $outputExtension === 'phar') {
            $this->convertZipToPhar($inputFile, $outputFile);
        } else {
            echo "Unsupported format conversion.\n";
        }
    }
}

if (count($argv) !== 3) {
    echo "Usage: php converter.php input_file output_file\n";
    echo "Supported formats: phar to zip, zip to phar\n";
    exit(1);
}

$inputFile = $argv[1];
$outputFile = $argv[2];

$pharConverter = new PharConverter($inputFile, $outputFile);
