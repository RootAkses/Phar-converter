<?php

namespace PharConverter;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class Main extends PluginBase
{
    private $inputPath;
    private $outputPath;

    public function onEnable()
    {
        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder() . "input/");
        @mkdir($this->getDataFolder() . "output/");

        $this->inputPath = $this->getDataFolder() . "input/";
        $this->outputPath = $this->getDataFolder() . "output/";

        $this->getLogger()->info("PharConverter aktif!");
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args)
    {
        if (strtolower($command->getName()) !== "pharconvert") {
            return false;
        }

        if (!$sender->hasPermission("pharconverter.use")) {
            $sender->sendMessage("§cKamu tidak memiliki permission!");
            return true;
        }

        if (count($args) < 2) {
            $sender->sendMessage("§e=== PharConverter ===");
            $sender->sendMessage("§f/pharconvert phar2zip <file>");
            $sender->sendMessage("§f/pharconvert zip2phar <file>");
            return true;
        }

        $type = strtolower($args[0]);
        $file = basename($args[1]);
        $inputFile = $this->inputPath . $file;

        if (!file_exists($inputFile)) {
            $sender->sendMessage("§cFile tidak ditemukan!");
            return true;
        }

        if ($type === "phar2zip") {
            $this->pharToZip($sender, $inputFile, $file);
            return true;
        }

        if ($type === "zip2phar") {
            $this->zipToPhar($sender, $inputFile, $file);
            return true;
        }

        $sender->sendMessage("§cMode tidak dikenal!");
        $sender->sendMessage("§f/pharconvert phar2zip <file>");
        $sender->sendMessage("§f/pharconvert zip2phar <file>");

        return true;
    }

    private function pharToZip(CommandSender $sender, $inputFile, $file)
    {
        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== "phar") {
            $sender->sendMessage("§cFile harus .phar!");
            return;
        }

        $baseName = pathinfo($file, PATHINFO_FILENAME);
        $outputFile = $this->outputPath . $baseName . ".zip";

        try {
            if (file_exists($outputFile)) {
                @unlink($outputFile);
            }

            $phar = new \Phar($inputFile);
            $phar->convertToData(\Phar::ZIP);

            $possibleFiles = array(
                $inputFile . ".zip",
                substr($inputFile, 0, -5) . ".zip"
            );

            $createdFile = null;

            foreach ($possibleFiles as $possible) {
                if (file_exists($possible)) {
                    $createdFile = $possible;
                    break;
                }
            }

            if ($createdFile === null) {
                $sender->sendMessage("§cFile ZIP tidak ditemukan!");
                return;
            }

            if ($createdFile !== $outputFile) {
                @rename($createdFile, $outputFile);
            }

            $sender->sendMessage("§aPHAR berhasil dikonversi!");
            $sender->sendMessage("§7Output: §f" . basename($outputFile));

        } catch (\Exception $e) {
            $sender->sendMessage("§cGagal: §f" . $e->getMessage());
        }
    }

    private function zipToPhar(CommandSender $sender, $inputFile, $file)
    {
        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== "zip") {
            $sender->sendMessage("§cFile harus .zip!");
            return;
        }

        $baseName = pathinfo($file, PATHINFO_FILENAME);
        $outputFile = $this->outputPath . $baseName . ".phar";
        $tempDir = $this->getDataFolder() . "temp_" . time() . "/";

        try {
            @mkdir($tempDir, 0777, true);

            $zip = new \ZipArchive();

            if ($zip->open($inputFile) !== true) {
                $sender->sendMessage("§cTidak dapat membuka ZIP!");
                $this->removeDirectory($tempDir);
                return;
            }

            if (!$zip->extractTo($tempDir)) {
                $zip->close();
                $sender->sendMessage("§cGagal mengekstrak ZIP!");
                $this->removeDirectory($tempDir);
                return;
            }

            $zip->close();

            if (file_exists($outputFile)) {
                @unlink($outputFile);
            }

            $phar = new \Phar($outputFile);
            $phar->buildFromDirectory($tempDir);
            $phar->setStub("<?php __HALT_COMPILER(); ?>");

            $this->removeDirectory($tempDir);

            $sender->sendMessage("§aZIP berhasil dikonversi!");
            $sender->sendMessage("§7Output: §f" . basename($outputFile));

        } catch (\Exception $e) {
            $this->removeDirectory($tempDir);
            $sender->sendMessage("§cGagal: §f" . $e->getMessage());
        }
    }

    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = @scandir($dir);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === "." || $item === "..") {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
