@php

$variableArray = array(
    'name'=>ucfirst($name),
    'email'=>$email,
    'phone'=>$phone,
    'email'=>$email,
    'messageText'=>$messageText,
);

$templateHTML = $template['content'];

foreach ($variableArray as $key => $value) {
    $templateHTML = str_replace("{".$key."}", $value, $templateHTML);
}

@endphp

{!! $templateHTML !!}