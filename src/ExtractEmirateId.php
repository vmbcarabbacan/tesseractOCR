<?php

namespace Vmbcarabbacan\TeseractOcr;

class ExtractEmirateId {
    
    public function emiratesId($string) {
        return [
            'emirates_id' => $this->id($string),
            'name' => $this->name($string),
            'dob' => $this->dob($string)
        ];
    }

    private function id($string) {
        $pattern = "/\b\d{3}-\d{4}-\d{7}-\d\b/";
        return $this->matches($string, $pattern);
    }

    private function name($string) {
        $pattern = "/Name: (.*?) Date/";
        $decodedString = htmlspecialchars_decode($string);
        $cleanString = str_replace(array("\r", "\n"), '', $decodedString);
        return $this->matches($cleanString, $pattern);
    }

    private function dob($string) {
        $pattern = "/\b\d{2}\/\d{2}\/\d{4}\b/";
        return $this->matches($string, $pattern);
    }

    private function matches($string, $pattern) {
        if (preg_match($pattern, $string, $matches)) {
            $extracted_text = $matches[0];
            return $extracted_text;
        } else {
            return null;
        }
    }
}