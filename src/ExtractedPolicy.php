<?php

namespace Vmbcarabbacan\TeseractOcr;

use DateTime;

class ExtractedPolicy {
    
    public function getPolicy($string) {
        return [
            'policy_no' => $this->getPolicyNo($string),
            'policy_start_date' => $this->getDueDate($string),
            'policy_end_date' => $this->getDueDate($string, 'to'),
            'string' => $this->cleanString($string)
        ];
    }

    private function getPolicyNo($string) {
        $an = '[a-zA-Z0-9]';
        $number = '[0-9]';

        $keywords = array(
            "/\b$an\/$an{2}\/$an{3}\/$an{4}\/$an{4}\/$an{5}\b/",
            "/\b$an{2}\/$an{2}\/$an{9}\/$an\/$an\b/",
            "/\b$an{2}\/$an{5}\/$an{2}\/$an\b/",
            "/\b$an{2}\/$an{2}\/$an{4}\/$an{2}\/$an{5}\b/",
            "/\b$an{2}\/$an{4}\/$an{3}\/$an{3}\b/",
            "/\b$an{2}\/$an{4}\/$an{2}\/$an{4}\/$an{4}\b/",
            "/\b$an{2}\/$an{3}\/$an{3}\/$an{4}\/$an{5}\b/",
            "/\b$an\/$an{4}\/$an{2}\/$an{4}\/$an{6}\b/",
            "/\b$an\/$an{2}\/$an{4}\/$an{4}\/$an{4}\b/",
            "/\b$an\/$an{2}\/$an{4}\/$an{2}\/$an{5}\b/",
            "/\b$an\/$an\/$an{3}\/$an{8}\b/",
            "/\b$an\/$an{2}\/$an{6}\b/",
            "/\b$an{3}\/$an\/$an{6}\b/",
            "/\b$an{3}\/$an{6}\b/",
            "/\b$an{2}\/$an{3}\/$an{4}\/$an{4}\b/",
            "/\b$an-$an{3}-$an{3}-$an{4}-$an{5}\b/",
            "/\b$an-$an{3}-$an{4}-$an{4}-$an{5}\b/",
            "/\b$an-$an{4}-$an{2}-$an{4}-$an{6}\b/",
            "/\b$an-$an{4}-$an{2}-$an{2}-$an{6}\b/",
            "/\b$an{2}-$an{4}-$an{6}\b/",
            "/\b$an{2}\/$an{6}\b/",
            "/\b$an{12}\b/",
            "/\b$number{22}\b/",
            "/\b$number{16}\b/",
            "/\b$number{9}\b/",
            "/\b$number{8}\b/",
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
            "/\\n\d{2}\s\w+\s\d{4}\s-\s\d{2}\s\w+\s\d{4}/",
            "/\b\d{2}\/\d{2}\/\d{4}\b to \b\d{2}\/\d{2}\/\d{4}\b/",
            "/\b\d{2}\/\d{2}\/\d{2} \d{4}\b\sto\s\b\d{2}\/\d{2}\/\d{2} \d{4}\b/",
            "/\b\d{2}\/\d{2}\/\d{4}\b \b\d{2}\/\d{2}\/\d{4}\b/",
            "/\b\d{2}\/\d{2}\/\d{4}\b\s-\s\b\d{2}\/\d{2}\/\d{4}\b/",
            "/\b\d{2}\/\d{2}\/\d{4}\b\s+to\s+\d{2}\/\d{2}\/\d{4}\b/"
        );

        $cleanedString = $this->cleanString($string);

        $extractedPatters = '';
        foreach($patterns as $patter) {
            if (preg_match(strtolower($patter), strtolower($cleanedString), $mitches)) {
                $extractedPatters = $mitches[0];
            } 
        }

        $dates = $this->explodeString($extractedPatters);
        $index = $type == 'from' ? 0 : 1;

        if(is_array($dates) && $dates[$index])
            return $this->correctDateFormat($dates[$index]);

        $otherPatterns = array(
            "/From+(\d{2}\/\d{2}\/\d{4})\s+To+(\d{2}\/\d{2}\/\d{4})/",
            "/Inception Date\s+(\d{2}\/\d{2}\/\d{4})\s+Expiry Date\s+(\d{2}\/\d{2}\/\d{4})/",
            "/\bAED\s+(\d{2}\/\d{2}\/\d{4})\s+To\s+(\d{2}\/\d{2}\/\d{4})\b/",
            "/on (\d{2}\/\d{2}\/\d{4}) and expires at \d{4} on (\d{2}\/\d{2}\/\d{4})/",
            "/Period From\s+(\d{2}\/\d{2}\/\d{4})\s+To\s+NORTH STAR INSURANCE BROKERS LLC\s+EMPIRE HEIGHTS TOWER A UNIT 1203\s+BUSINESS BAY PO BOX 35432\s+(\d{2}\/\d{2}\/\d{4})\s+A\/c No/",
            "/FROM\s+(\d{2}\/\d{2}\/\d{4})\s+\d{2}:\d{2}\s+\w+\s+To\s+(\d{2}\/\d{2}\/\d{4})\s+\d{2}:\d{2}\s+\w+/",
            "/PERIOD OF INSURANCE FROM\s+(\d{2}\/\d{2}\/\d{4})\s+\d{4}\s+TO\s+(\d{2}\/\d{2}\/\d{4})\s+\d{4}\s+\w+\s+Specification of Insured Vehicles/s",
            "/PERIOD OF INSURANCE  FROM\s+(\d{2}\/\d{2}\/\d{4})\s+\d{4}\s+Hrs TO\s+(\d{2}\/\d{2}\/\d{4})\s+\d{4}\s+Hrs/",
            "/PERIOD OF INSURANCE  FROM\s+(\d{2}\/\d{2}\/\d{4})\s+\d{4}\s+TO\s+(\d{2}\/\d{2}\/\d{4})\s+\d{4}\s+Hrs/",
            "/TO\s+(\d{2}\/\d{2}\/\d{4})\s+\d{4}\s+\w+\s+(\d{2}\/\d{2}\/\d{4})\s+\d{4}\s+\w/",
            "/From\s+(\d{2}-\w+\s+-\d{4})\s+\d{4}\s+\w+\\n+\w+\s+(\d{2}-\w+\s+-\d{4})\s+\d{4}/",
            "/From\s+(\d{2}-\w+-\d{4})\s+\d{4}\s+\w+\\n+\w+\s+(\d{2}-\w+-\d{4})\s+\d{4}/",
            "/\\n(\d{2}\s\w+\s\d{4})\s+\d{6}\s+-\s+(\d{2}\s+\w+\s+\d{4})\s+\d{6}/",
            "/\\n(\d{2}-\w+-\d{4})\\n\\n\\n\\n\s+\\n\\nPolicy Expiry Date\\n\\n\s+(\d{2}-\w+-\d{4})/",
            "/\\n(\d{2}-\w+-\d{4})\\n\\n\\n\\n\s+\\n\\nPolicy Inception Date\\n\\n\\n\\nPolicy Expiry Date\\n\\n\s+(\d{2}-\w+-\d{4})/",
            "/\\n(\d{2}\/\d{2}\/\d{4})\\n+(\d{2}\/\d{2}\/\d{4})/",
            "/\\n(\d{2}[.]?\d{2}[.]?\d{4})\s+\w+\s+(\d{2}[.]?\d{2}[.]?\d{4})/",
            "/\w+\s+(\d{2}\/\d{2}\/\d{4})\s+\w+\s+\w+\s+(\d{2}\/\d{2}\/\d{4})/",
            "/(\d{2}\/\d{2}\/\d{4})\s+-\s+\w+\s+-\s+(\d{2}\/\d{2}\/\d{4})/"
        );

        foreach($otherPatterns as $pattern){
            $others = $this->getOtherDates($cleanedString, $pattern, $type);
            if($others)
                return $others;
        }

    }

    private function getOtherDates($string, $pattern, $type) {
        try {
            if (preg_match(strtolower($pattern), strtolower($string), $matches)) {
                $index = $type == 'from' ? 1 : 2;
                // return $matches;
                return $this->correctDateFormat($matches[$index]);
            } 
        } catch (\Exception $e) {
            return null;
        } 
    }

    private function explodeString($string) {
        $arrs = array('to', ' - ', ' ', '-');
        foreach($arrs as $arr) {
            $data = explode($arr, $string);
            if(is_array($data) && count($data) > 1)
                return $data;
        }
        return;
    }

    private function correctDateFormat($date) {
        try {
            $formats = array('d-M-y', 'd/m/y', 'd/m/Y', 'dmY', 'd/m/y H:i', 'd/m/Y H:i', 'd/m/y Hi');

            foreach($formats as $format) {
                $newDate = DateTime::createFromFormat($format, trim($date));
                if($newDate)
                    return $newDate->format('Y-m-d');
            }

            $date = new DateTime(trim($date));
            if($date) 
                return $date->format('Y-m-d');

        } catch(\Exception $e) {
            return null;
        }
    }

    public function cleanString ($string) {
        $decodedString = htmlspecialchars_decode($string);
        $pattern = "/[^A-Za-z0-9\s\-\/]/";
        $cleanedString = preg_replace($pattern, '', $decodedString);

        return $cleanedString;
    }
}