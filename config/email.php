<?php
function email_config(){
    static $config=null;
    if($config!==null)return $config;

    $config=[
        'brevo_api_key'=>'',
        'from_email'=>'',
        'from_name'=>'StoreBridge',
        'app_url'=>''
    ];

    $local=__DIR__.'/email.local.php';
    if(is_file($local)){
        $localConfig=require $local;
        if(is_array($localConfig))$config=array_merge($config,$localConfig);
    }

    $config['app_url']=rtrim($config['app_url']?:('https://'.($_SERVER['HTTP_HOST']??'localhost')),'/');
    return $config;
}

function send_password_reset_email($toEmail,$toName,$resetUrl){
    $c=email_config();

    if(!$c['brevo_api_key']||!$c['from_email']){
        error_log('StoreBridge password reset email is not configured.');
        return false;
    }

    if(!function_exists('curl_init')){
        error_log('StoreBridge password reset email requires PHP cURL.');
        return false;
    }

    $safeName=htmlspecialchars($toName,ENT_QUOTES,'UTF-8');
    $safeUrl=htmlspecialchars($resetUrl,ENT_QUOTES,'UTF-8');

    $payload=[
        'sender'=>[
            'name'=>$c['from_name'],
            'email'=>$c['from_email']
        ],
        'to'=>[
            ['email'=>$toEmail,'name'=>$toName]
        ],
        'subject'=>'Reset your StoreBridge password',
        'htmlContent'=>'<p>Hello '.$safeName.',</p><p>We received a request to reset your StoreBridge password.</p><p><a href="'.$safeUrl.'">Reset your password</a></p><p>This link expires in 1 hour and can only be used once.</p><p>If you did not request this, you can ignore this email.</p>',
        'textContent'=>"Hello ".$toName.",\n\nReset your StoreBridge password:\n".$resetUrl."\n\nThis link expires in 1 hour and can only be used once.\n\nIf you did not request this, you can ignore this email."
    ];

    $json=json_encode($payload);
    if($json===false){
        error_log('StoreBridge password reset email payload encoding failed.');
        return false;
    }

    $ch=curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch,[
        CURLOPT_POST=>true,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_HTTPHEADER=>[
            'accept: application/json',
            'api-key: '.$c['brevo_api_key'],
            'content-type: application/json'
        ],
        CURLOPT_POSTFIELDS=>$json,
        CURLOPT_TIMEOUT=>15,
        CURLOPT_USERAGENT=>'StoreBridge password reset'
    ]);

    $response=curl_exec($ch);
    $httpCode=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $curlError=curl_error($ch);
    curl_close($ch);

    if($response===false||$httpCode<200||$httpCode>=300){
        error_log('StoreBridge password reset email failed: HTTP '.$httpCode.' '.$curlError);
        return false;
    }

    return true;
}
