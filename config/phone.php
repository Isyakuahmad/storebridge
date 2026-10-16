<?php
function normalize_nigerian_whatsapp($value){
    $number=preg_replace('/\D+/','',(string)$value);

    if(str_starts_with($number,'0')){
        $number='234'.substr($number,1);
    }elseif(str_starts_with($number,'234')){
        // Already in Nigeria international format.
    }else{
        return false;
    }

    return preg_match('/^234(?:70|80|81|90|91)\d{8}$/',$number)
        ? $number
        : false;
}
