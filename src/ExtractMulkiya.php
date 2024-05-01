<?php

namespace Vmbcarabbacan\TeseractOcr;

class ExtractMulkiya extends ExtractedPolicy {
    
    public function getMulkiya($string) {
        return [
            'chassis_number' => $this->chassisNumber($string),
            'string' => $this->cleanString($string)
        ];
    }

    private function chassisNumber($string) {
        $an = '[a-zA-Z0-9]';

        $keywords = array(
            "/\b$an{17}\b/",
            "/\b$an{18}\b/",
            "/\b$an{19}\b/",
            "/\b$an{20}\b/",
        );

        foreach($keywords as $keyword) {
            if (preg_match($keyword, $string, $matches)) {
                $extracted_text = $matches[0];
                return $extracted_text;
            } 
        }
    }
}