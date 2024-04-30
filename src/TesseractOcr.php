<?php
namespace Vmbcarabbacan\TeseractOcr;

use thiagoalessio\TesseractOCR\TesseractOCR as TesOCR;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Smalot\PdfParser\Parser;
use Imagick;

class TesseractOcr extends ExtractEmirateId {
    
    private $imagePath, $basePath, $baseName, $extension, $lang = null;
    private $path, $isEmirateId = true, $isPolicy = false;
    private $gs = 'gs';
    private $isImagePDF = false;

    public function __construct(String $path = null)
    {
        $this->path = $path;
    }

    public function generateFile() {
        if(in_array(strtolower($this->extension), ['jpg', 'jpeg', 'png'])) {
            $processImage = $this->processImage($this->imagePath);
            $data = $this->runImageOCR($processImage);
        } else if(strtolower($this->extension) == 'pdf') {
            $data = $this->isEmirateId 
            ? $this->pdfToPng($this->imagePath) 
            : $this->runPdfOCR($this->imagePath);
        } else {
            return 'Unable to read the file ' . $this->baseName . '-' .$this->extension;
        }

        if($this->isEmirateId) {
            @unlink($this->basePath.'/process-'.$this->baseName);
            return $this->getEmiratesId($data);
        }

        if($this->isPolicy) {
            if(!$data) 
                $data = $this->pdfToPng($this->imagePath);
            return $this->getPolicy($data);
        }
            
    }
    
    public function raw() {
        if(in_array(strtolower($this->extension), ['jpg', 'jpeg', 'png'])) {
            $processImage = $this->processImage($this->imagePath);
            $data = $this->runImageOCR($processImage);
        } else if(strtolower($this->extension) == 'pdf') {
            $data = $this->isImagePDF 
            ? $this->pdfToPng($this->imagePath) 
            : $this->runPdfOCR($this->imagePath);
        } else {
            return 'Unable to read the file ' . $this->baseName . '-' .$this->extension;
        }

        $po = new ExtractedPolicy();
        return $po->cleanString($data);
    }
    
    public function setpath($imagePath){
        $this->imagePath = $imagePath;
        $this->extension = pathinfo($imagePath, PATHINFO_EXTENSION);
        $this->basePath = dirname($imagePath);
        $this->baseName = basename($imagePath);

        return $this;
    }

    public function lang($lang) {
        $this->lang = $lang;

        return $this;
    }

    public function setGs($gs) {
        $this->gs = $gs;

        return $this;
    }

    public function emiratesId() {
        $this->isEmirateId = true;
        $this->isPolicy = false;
        
        return $this;
    }

    public function pdfImage() {
        $this->isImagePDF = true;

        return $this;
    }

    public function policy() {
        $this->isEmirateId = false;
        $this->isPolicy = true;

        return $this;
    }

    private function runImageOCR($image) {
        try {
            $ocr = new TesOCR($image);
            if(!is_null($this->path)) $ocr->executable($this->path);
            if(!is_null($this->lang)) $ocr->lang($this->lang);
            $data = $ocr->dpi(300)->run();

            return $data;
        } catch(\Exception $e) {
            return $e->getMessage();
        }
    }

    private function runPdfOCR($path) {
        try {

            $parser = new Parser();

            $pdf = $parser->parseFile($path);
            $text = $pdf->getText();

            return $text;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    private function pdfToPng($image) {
        try {
            $outputFile = $this->basePath . '/pdtToPng/';
            $this->createFolder($outputFile);
            // Use Ghostscript to decrypt PDF and convert to images
            $process = new Process([$this->gs, '-q', '-dNOPAUSE', '-dBATCH', '-sDEVICE=png16m', '-sOutputFile=' . $outputFile . 'page%d.png', $image]);
            $process->run();

            // Check if Ghostscript command was successful
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // Iterate through generated images and extract text using Tesseract OCR
            $extractedText = '';
            $imageFiles = glob($outputFile . '*.png');
            foreach ($imageFiles as $imageFile) {
                $ocr = new TesOCR($imageFile);
                if(!is_null($this->path)) $ocr->executable($this->path);
                if(!is_null($this->lang)) $ocr->lang($this->lang);
                $extractedText .= $ocr->run();
            }

            $this->deleteFolderAndFiles($outputFile);

            // Output the extracted text
            return $extractedText;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    private function processImage($imagePath) {
        try {
            $image = new Imagick($imagePath);

            // Binarization
            $image->thresholdImage(25000); // Adjust threshold value as needed

            // Noise removal (morphology)
            $kernel = [ // Define the kernel for morphology operations
                [0, 1, 0], 
                [1, 1, 1], 
                [0, 1, 0]
            ];

            $image->convolveImage($kernel);

            // Skew correction
            $image->deskewImage(40); // Adjust threshold angle as needed

            // Save or output the processed image
            $outputImagePath = $this->basePath.'/process-'.$this->baseName;
            $image->writeImage($outputImagePath);

            $image->destroy();

            return $outputImagePath;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    private function deleteFolderAndFiles($path) {
        if (!is_dir($path)) {
            return "$path must be a directory";
        }
        if (substr($path, strlen($path) - 1, 1) !== '/') {
            $path .= '/';
        }
        $files = glob($path . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteFolderAndFiles($file);
            } else {
                unlink($file);
            }
        }
        rmdir($path);
    }

    private function createFolder($folderPath) {
        if (!is_dir($folderPath)) {
            // Create the folder if it doesn't exist
            if (!mkdir($folderPath, 0755, true)) { // 0755 is the default permissions, true creates nested directories if needed
                // 
            }
        }

        return $folderPath;
        
    }
}