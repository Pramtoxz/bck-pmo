<?php

namespace App\Services;

use App\Models\OTP;
use Carbon\Carbon;
use App\Providers\WhatsAppGateway;

class OTPService
{
    protected $whatsapp;

    public function __construct()
    {
        $this->whatsapp = new WhatsAppGateway();
    }

    public function generateOTP(string $nohp, string $type = 'register'): string
    {
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        OTP::create([
            'nohp' => $nohp,
            'otp_code' => $otpCode,
            'type' => $type,
            'is_used' => false,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        return $otpCode;
    }

    public function sendOTP(string $nohp, string $otpCode): void
    {
        $message = "Kode OTP Anda: *{$otpCode}*\n\nKode berlaku selama 5 menit.\nJangan bagikan kode ini kepada siapapun.";
        
        $this->whatsapp->sendText($nohp, $message);
    }

    public function verifyOTP(string $nohp, string $otpCode): bool
    {
        $otp = OTP::where('nohp', $nohp)
            ->where('otp_code', $otpCode)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otp) {
            return false;
        }

        $otp->update(['is_used' => true]);
        return true;
    }
}
