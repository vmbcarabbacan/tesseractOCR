<?php

namespace Vmbcarabbacan\TeseractOcr;

class ExtractedPolicy {
    
    public function policy($string) {
        return [
            'policy_no' => $this->getPolicyNo($string),
            'policy_start_date' => $this->getDueDate($string),
            'policy_end_date' => $this->getDueDate($string, 'to'),
            'string' => $string
        ];
    }

    private function getPolicyNo($string) {
        $an = '[a-zA-Z0-9]';

        $keywords = array(
            "/\b$an\/$an{2}\/$an{4}\/$an{2}\/$an{5}\b/",
            "/\b$an\/$an{2}\/$an{6}\b/",
            "/\b$an{2}\/$an{2}\/$an{9}\/$an\/$an\b/",
            "/\b$an{2}\/$an{3}\/$an{3}\/$an{4}\/$an{5}\b/",
            "/\b$an{2}-$an{4}-$an{6}\b/",
            "/\b$an\/$an\/$an{3}\/$an{8}\b/",
            "/\b$an\/$an{2}\/$an{4}\/$an{4}\/$an{4}\b/",
            "/\b$an{3}\/$an\/$an{6}\b/",
            "/\b$an{3}\/$an{6}\b/",
            "/\b$an-$an{3}-$an{3}-$an{4}-$an{5}\b/",
            "/\b$an-$an{3}-$an{4}-$an{4}-$an{5}\b/"
        );
    
        foreach($keywords as $keyword) {
            if (preg_match($keyword, $string, $matches)) {
                $extracted_text = $matches[0];
                return $extracted_text;
            } 
        }
    }

    private function getDueDate($string, $type = 'from') {

        $patterns = array(
            "/\b\d{2}-[a-zA-Z0-9]{3}-\d{2}\b to \b\d{2}-[a-zA-Z0-9]{3}-\d{2}\b/",
            "/\b\d{2}\/\d{2}\/\d{4}\b to \b\d{2}\/\d{2}\/\d{4}\b/",
            "/\b\d{2}\/\d{2}\/\d{2} \d{4}\b\sto\s\b\d{2}\/\d{2}\/\d{2} \d{4}\b/",
            "/\b\d{2}\/\d{2}\/\d{4}\b \b\d{2}\/\d{2}\/\d{4}\b/",
            "/\b\d{2}\/\d{2}\/\d{4}\b\s-\s\b\d{2}\/\d{2}\/\d{4}\b/",
            "/\b\d{2}\/\d{2}\/\d{4}\b\s+to\s+\d{2}\/\d{2}\/\d{4}\b/"
        );

        $decodedString = htmlspecialchars_decode($string);

        $pattern = "/[^A-Za-z0-9\s\-\/]/";
        $cleanedString = preg_replace($pattern, '', $decodedString);

        $extractedPatters = '';
        foreach($patterns as $patter) {
            if (preg_match(strtolower($patter), strtolower($cleanedString), $mitches)) {
                $extractedPatters = $mitches[0];
            } 
        }

        $dates = $this->explodeString($extractedPatters);
        $index = $type == 'from' ? 0 : 1;

        if(is_array($dates) && $dates[$index])
            return $dates[$index];

        $otherPatterns = array(
            "/From+(\d{2}\/\d{2}\/\d{4})\s+To+(\d{2}\/\d{2}\/\d{4})/",
            "/Inception Date\s+(\d{2}\/\d{2}\/\d{4})\s+Expiry Date\s+(\d{2}\/\d{2}\/\d{4})/",
            "/\bAED\s+(\d{2}\/\d{2}\/\d{4})\s+To\s+(\d{2}\/\d{2}\/\d{4})\b/",
            "/on (\d{2}\/\d{2}\/\d{4}) and expires at \d{4} on (\d{2}\/\d{2}\/\d{4}/",
            "/Period From\s+(\d{2}\/\d{2}\/\d{4})\s+To\s+NORTH STAR INSURANCE BROKERS LLC\s+EMPIRE HEIGHTS TOWER A UNIT 1203\s+BUSINESS BAY PO BOX 35432\s+(\d{2}\/\d{2}\/\d{4})\s+A\/c No/"
        );
        
        foreach($otherPatterns as $pattern){
            $others = $this->getOtherDates($cleanedString, $pattern, $type);
            if($others)
                return $others;
        }
    }

    private function getOtherDates($string, $pattern, $type) {
        if (preg_match(strtolower($pattern), strtolower($string), $matches)) {
            if($type == 'from') return $matches[1];
            else return $matches[2];
        } 
    }

    private function explodeString($string) {
        $arrs = array('to', ' ', '-');
        foreach($arrs as $arr) {
            $data = explode($arr, $string);
            if(is_array($data) && count($data) > 1)
                return $data;
        }
        return;
    }
}