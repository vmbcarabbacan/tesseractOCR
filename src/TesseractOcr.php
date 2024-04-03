<?php
namespace Vmbcarabbacan\TeseractOcr;

use thiagoalessio\TesseractOCR\TesseractOCR as TesOCR;
use Vmbcarabbacan\TeseractOcr\ExtractEmirateId;
use Vmbcarabbacan\TeseractOcr\ExtractedPolicy;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Imagick;

class TesseractOcr {
    
    private $imagePath, $basePath, $baseName, $extension, $lang = null;
    private $path, $is_emirate_id = true, $is_policy = false;
    private $gs = 'gs';

    public function __construct(String $path = null)
    {
        $this->path = $path;
    }

    public function test() {
        return 'this is vmbcarabbacan tesseract';
    }

    public function generateFile() {
        if(in_array(strtolower($this->extension), ['jpg', 'jpeg', 'png'])) {
            $processImage = $this->processImage($this->imagePath);
            $data = $this->runImageOCR($processImage);
        } else if(strtolower($this->extension) == 'pdf') {
            $data = $this->is_emirate_id 
            ? $this->pdfToPng($this->imagePath) 
            : $this->runPdfOCR($this->imagePath);
        } else {
            return 'Unable to read the file ' . $this->baseName . '-' .$this->extension;
        }

        if($this->is_emirate_id) {
            @unlink($this->basePath.'/process-'.$this->baseName);
            $em = new ExtractEmirateId();
            return $em->emiratesId($data);
        }

        if($this->is_policy) {
            $po = new ExtractedPolicy();
            return $po->policy($data);
        }
            
    }
    
    public function setImage($imagePath){
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

    public function emirateId() {
        $this->is_emirate_id = true;
        $this->is_policy = false;
        
        return $this;
    }

    public function policy() {
        $this->is_emirate_id = false;
        $this->is_policy = true;

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

            $output = [];
            exec("pdftotext " . escapeshellarg($path) . " -", $output);
            return implode("\n", $output);

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    private function pdfToPng($image) {
        try {
            // Use Ghostscript to decrypt PDF and convert to images
            $process = new Process([$this->gs, '-q', '-dNOPAUSE', '-dBATCH', '-sDEVICE=png16m', '-sOutputFile=' . $this->basePath . 'page%d.png', $image]);
            $process->run();

            // Check if Ghostscript command was successful
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // Iterate through generated images and extract text using Tesseract OCR
            $extractedText = '';
            $imageFiles = glob($this->basePath . '*.png');
            foreach ($imageFiles as $imageFile) {
                $ocr = new TesOCR($imageFile);
                if(!is_null($this->path)) $ocr->executable($this->path);
                if(!is_null($this->lang)) $ocr->lang($this->lang);
                $extractedText .= $ocr->run();
            }

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
}