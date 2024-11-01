<?php

namespace Vmbcarabbacan\TeseractOcr;

class ExtractEmirateId extends ExtractedPolicy {
    
    public function getEmiratesId($string) {
        return [
            'emirates_id' => $this->id($string),
            'name' => $this->name($string),
            'dob' => $this->dob($string),
            'expiry_date' => $this->expiry($string),
            'string' => $this->cleanString($string)
        ];
    }

    private function id($string) {
        $pattern = "/\b\d{3}-\d{4}-\d{7}-\d\b/";
        return $this->matches($string, $pattern);
    }

    private function name($string) {
        $pattern = "/Name:\s*(.*?)\s*Date/";
        $decodedString = htmlspecialchars_decode($string);
        $cleanString = str_replace(array("\r", "\n"), '', $decodedString);
        return $this->matches($cleanString, $pattern, 1);
    }

    private function dob($string) {
        $pattern = "/\b\d{2}\/\d{2}\/\d{4}\b/";
        return $this->matches($string, $pattern);
    }

    private function expiry($string) {
        $pattern = "/\b\d{2}\/\d{2}\/\d{4}\b/";
        preg_match_all($pattern, $string, $matches);
        if($matches) {
            $matches = $matches[0];
            $count = count($matches);
            return $matches[$count - 1];
        }

        return null;
    }

    private function matches($string, $pattern, $index = 0) {
        try{
            if (preg_match($pattern, $string, $matches)) {
                $extracted_text = $matches[$index];
                return $extracted_text;
            } else {
                return null;
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}