<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\SipAccount;
use Illuminate\Support\Facades\Hash;

class TeleAgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $serverIp = '192.168.120.33'; // Or use config('app.url')
        $wsServer = 'ws://' . $serverIp . ':8088/ws'; // Defaulting to Insecure for Manual Fallback compat
        
        for ($i = 1; $i <= 43; $i++) {
            $username = "tele-$i";
            $sipExtension = 1000 + $i; // 1001 ... 1043
            $trunkAccount = "880170" . str_pad($i, 2, '0', STR_PAD_LEFT);

            // 1. Create User
            $user = User::firstOrCreate(
                ['username' => $username],
                [
                    'name' => "Tele Agent $i",
                    'email' => "$username@warehouse.local",
                    'password' => Hash::make('password123'), // Default password
                    'role' => User::ROLE_AGENT,
                    'is_active' => true,
                ]
            );

            // 2. Create/Update SIP Account (WebRTC Credentials)
            SipAccount::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => "Start Point Agent $i",
                    'sip_server' => $serverIp,
                    'ws_server' => $wsServer,
                    'username' => (string)$sipExtension,
                    'password' => 'webrtc_secret', // Matches pjsip.conf
                    'display_name' => "Agent $i",
                    'is_active' => true,
                    'is_default' => true,
                    'realm' => 'asterisk',
                    // Store trunk info in options for reference if needed
                    'options' => ['trunk_username' => $trunkAccount]
                ]
            );

            $this->command->info("Processed $username -> SIP $sipExtension (Trunk $trunkAccount)");
        }
    }
}
