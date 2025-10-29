<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (!function_exists('api_response')) {
    function api_response(bool $success = true, string $message = '', $data = [], $code = 200)
    {
        return response()->json(['success' => $success, 'message' => $message, 'data' => $data], $code);
    }
}

if (!function_exists('sanitize_file_name')) {
    function sanitize_file_name($filename): string
    {
        // Remove invalid characters (except alphanumeric, ., -, and _)
        $sanitized = preg_replace('/[^a-zA-Z0-9\.\_-]/', '_', $filename);

        $sanitized = trim($sanitized, ".-_");

        if (strlen($sanitized) > 255) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $sanitized = substr($sanitized, 0, 255 - strlen($extension) - 1) . '.' . $extension;
        }

        return $sanitized;
    }
}

if (!function_exists('uploadFile')) {
    function uploadFile($file, $path)
    {

        $name_file = date('Y-m-d_His-') . Str::random(6) . auth()->id() . '-' . sanitize_file_name($file->getClientOriginalName());
        $file->storeAs($path, $name_file, 'public');
        $url = Storage::disk('public')->url('/' . $path . '/' . $name_file);
        return $url;
    }
}

function deleteFile($fileUrl)
{
     $filePath = str_replace(Storage::disk('public')->url(''), '', $fileUrl);
    return Storage::disk('public')->delete($filePath);
}

if (!function_exists('message_sender')) {
    function message_sender($from, $to, string $message = '') {

        $url = config('otp.sendApiUrl'); //url du serveur
        $apiKey = config('otp.apiKey'); //remplacez par votre api key
        $clientId = config('otp.clientId'); //Remplacez par votre client Id
        $curl = curl_init(); 
        $smsData   = array(
            'from' => $from, //l'expediteur
            'to' =>''.$to.'', //destination au format international sans "+" ni "00". Ex: 22890443679
            'type' => 0, //type de message text et flash
            'message' => $message, //le contenu de votre sms
            'dlr' => 1 // 1 pour un retour par contre 0
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("APIKEY: " . $apiKey, "CLIENTID:" . $clientId));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,   $smsData);
        $result = curl_exec($ch);
        curl_close($ch);
        
        return $result;
    }
}

if (!function_exists('format_money')) {
    function format_money($amount)
    {
        return number_format($amount, 2, '.', ',');
    }
}

if (!function_exists('format_date')) {
    function format_date($date)
    {
        return $date ? date('Y-m-d', strtotime($date)) : null;
    }
}

// Add any other helper functions you need here