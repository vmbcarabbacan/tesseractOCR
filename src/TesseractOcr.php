<?php
require 'vendor/autoload.php';

namespace Vmbcarabbacan\TeseractOcr;

use thiagoalessio\TesseractOCR\TesseractOCR as TesOCR;
use Spatie\PdfToImage\Pdf;
use Vmbcarabbacan\TeseractOcr\ExtractEmirateId;

class TesseractOcr {
    
    private $imagePath, $basePath, $baseName, $extension;
    private $path, $is_emirate_id = true, $is_policy = false;

    public function __construct(String $path = null)
    {
        $this->path = $path;
    }

    public function generateFile() {
        if(in_array(strtolower($this->extension), [IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
            $newImage = $this->createImageInGrayScale($this->imagePath);
            $data = $this->runImageOCR($newImage);
        } else if(strtolower($this->extension) == 'pdf') {
            $data = $this->runPdfOCR($this->imagePath);
        } else {
            return 'Unable to read the file ' . $this->baseName;
        }

        if($this->is_emirate_id) {
            $em = new ExtractEmirateId();
            return $em->emiratesId($data);
        }

        if($this->is_policy) {
            return 'to be finished';
        }
            
    }

    
    public function setImage($imagePath) :void {
        $this->imagePath = $imagePath;
        $this->extension = pathinfo($imagePath, PATHINFO_EXTENSION);
        $this->basePath = dirname($imagePath);
        $this->baseName = basename($imagePath);
    }

    public function emirateId() :void {
        $this->is_emirate_id = true;
        $this->is_policy = false;
    }

    public function policy() :void {
        $this->is_emirate_id = false;
        $this->is_policy = true;
    }

    private function runImageOCR($image) {
        try {
            $ocr = new TesOCR($image);
            if(!is_null($this->path)) $ocr->executable($this->path);
            $ocr->lang('en');
            $data = $ocr->run();

            return $data;
        } catch(\Exception $e) {
            return $e->getMessage();
        }
    }

    private function runPdfOCR($path) {
        try {
            $pdf = new Pdf($path);
            $pdf->setResolution(300); // Set resolution (optional)
            $pdf->setOutputFormat('png'); // Set output format (optional)
            $imagePaths = $pdf->saveAllPages($this->basePath.'/PDF'); // Save images to a directory

            foreach ($imagePaths as $imagePath) {
                $ocr = new TesOCR($imagePath);
                $ocr->lang('eng'); // Set language (optional)
                $extractedText = $ocr->run();
        
                // Output the extracted text
                return $extractedText;
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    private function createImageInGrayScale($originalImage) {
        try {
            if($this->extension == IMAGETYPE_JPEG)
                $originalImage = imagecreatefromjpeg($originalImage);
            else if ($this->extension == IMAGETYPE_PNG)
                $originalImage = imagecreatefrompng($originalImage);
            else return false;

            // Get the width and height of the image
            $width = imagesx($originalImage);
            $height = imagesy($originalImage);

            // Create a new image with black and white color
            $grayscaleImage  = imagecreatetruecolor($width, $height);

            // Convert the original image to grayscale (PNG)
            for ($x = 0; $x < $width; $x++) {
                for ($y = 0; $y < $height; $y++) {
                    $rgb = imagecolorat($originalImage, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $gray = round(0.299 * $r + 0.587 * $g + 0.114 * $b);
                    $grayColor = imagecolorallocate($grayscaleImage, $gray, $gray, $gray);
                    imagesetpixel($grayscaleImage, $x, $y, $grayColor);
                }
            }

            // Save or output the grayscale image as PNG
            $path =  $this->basePath.'/gray-'.$this->baseName;
            imagepng($grayscaleImage, $path);

            // Clean up resources
            // imagedestroy($originalImage);
            // imagedestroy($grayscaleImage);
            return $path;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}