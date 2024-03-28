<?php
namespace Vmbcarabbacan\TeseractOcr;

use thiagoalessio\TesseractOCR\TesseractOCR as TesOCR;
use Spatie\PdfToImage\Pdf;
use Vmbcarabbacan\TeseractOcr\ExtractEmirateId;
use Imagick;

class TesseractOcr {
    
    private $imagePath, $basePath, $baseName, $extension, $lang = null;
    private $path, $is_emirate_id = true, $is_policy = false;

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
            $newImage = $this->createImageInGrayScale($this->imagePath);
            $data = $this->runImageOCR($newImage);
        } else if(strtolower($this->extension) == 'pdf') {
            $data = $this->runPdfOCR($this->imagePath);
        } else {
            return 'Unable to read the file ' . $this->baseName . '-' .$this->extension;
        }

        if($this->is_emirate_id) {
            $em = new ExtractEmirateId();
            return $em->emiratesId($data);
        }

        if($this->is_policy) {
            return 'to be finished';
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
            $pdf = new Pdf($path);
            $pdf->setResolution(300); // Set resolution (optional)
            $pdf->setOutputFormat('png'); // Set output format (optional)
            $imagePaths = $pdf->saveAllPages($this->basePath.'/PDF'); // Save images to a directory

            foreach ($imagePaths as $imagePath) {
                $ocr = new TesOCR($imagePath);
                if(!is_null($this->path)) $ocr->executable($this->path);
                if(!is_null($this->lang)) $ocr->lang($this->lang);
                $extractedText = $ocr->dpi(300)->run();
        
                // Output the extracted text
                return $extractedText;
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    private function createImageInGrayScale($originalImage) {
        try {
            if(in_array($this->extension, ['jpg', 'jpeg']))
                $originalImage = imagecreatefromjpeg($originalImage);
            else if ($this->extension == 'png')
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
                    $alpha = ($rgb >> 24) & 0xFF; // Extract alpha channel
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $gray = round(0.299 * $r + 0.587 * $g + 0.114 * $b);
                    $bwColor = imagecolorallocatealpha($grayscaleImage, $gray, $gray, $gray, $alpha); // Preserve alpha channel
                    imagesetpixel($grayscaleImage, $x, $y, $bwColor);
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

    private function processImage($imagePath) {
        try {
            $imagick = new Imagick($imagePath);

            // Convert the image to grayscale
            // $imagick->modulateImage(100,0,100);
            // $imagick->transformImageColorspace(Imagick::COLORSPACE_GRAY);

            // Apply a Gaussian blur for noise removal
            $imagick->gaussianBlurImage(1, 0.5);

            // Binarize the image using Otsu's method
            // $imagick->thresholdImage(0); // Automatic thresholding using Otsu's method

            // Correct image skew
            // $imagick->deskewImage(40); // Adjust the threshold angle as needed

            // Save the processed image
            
            $outputImagePath = $this->basePath.'/process-'.$this->baseName;
            $imagick->writeImage($outputImagePath);

            // Destroy the Imagick object to free up memory
            $imagick->clear();
            $imagick->destroy();

            return $outputImagePath;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}