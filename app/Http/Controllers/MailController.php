<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Exception;
use Illuminate\Support\Facades\Log;

class MailController extends Controller
{
    // adex developer
    public static function send_mail($user_data, $template)
    {
        try {
            Mail::send($template, $user_data, function ($message) use ($user_data) {
                $message->to($user_data['email'], $user_data['username'])
                    ->subject($user_data['title']);
                $message->from(config('mail.from.address'), $user_data['app_name']);
            });

            if (Mail::failures()) {
                Log::error('Mail sending failed for: ' . $user_data['email']);
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::error('Mail sending exception: ' . $e->getMessage());
            return false;
        }
    }
}