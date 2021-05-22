<?php

namespace App\Services\Sanitize;

class SanitizeService {
    public function sanitizeAllFields($data){
        $data = (array)$data;

        foreach($data as $key => $value){
            if(is_array($value)){
                $data[$key] = $this->sanitizeAllFields($value);
            } else {
                $data[$key] = $this->sanitizeField($value);
            }
        }

        return $data;
    }

    public function sanitizeField($string){
        
        if(!$string){
            return;
        }

        $string = str_replace('\n', "\n", $string);
        $string = strip_tags($string);
        $string = preg_replace( "/\r/", "", $string);
        $string = htmlentities($string, ENT_QUOTES,'UTF-8',false);
        
        return $string;
    }

    public function decodeAllFields($data){
        $data = (array)$data;

        foreach($data as $key => $value){
            if(is_array($value)){
                $data[$key] = $this->decodeAllFields($value);
            } else {
                $data[$key] = $this->decodeField($value);
            }
        }

        return $data;
    }

    public function decodeField($string){
        return html_entity_decode($string, ENT_QUOTES, 'UTF-8');
    }
}
?>