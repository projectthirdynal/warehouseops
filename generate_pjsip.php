<?php

$lines = [];

// Transports
$lines[] = "; --- TRANSPORTS ---";
$lines[] = "[transport-wss]";
$lines[] = "type=transport";
$lines[] = "protocol=wss";
$lines[] = "bind=0.0.0.0:8089";
$lines[] = "cert_file=/etc/asterisk/keys/asterisk.crt";
$lines[] = "priv_key_file=/etc/asterisk/keys/asterisk.key";
$lines[] = "cipher=ECDHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-AES128-GCM-SHA256";
$lines[] = "method=tlsv1_2";
$lines[] = "";
$lines[] = "[transport-ws]";
$lines[] = "type=transport";
$lines[] = "protocol=ws";
$lines[] = "bind=0.0.0.0:8088";
$lines[] = "";
$lines[] = "[transport-udp]";
$lines[] = "type=transport";
$lines[] = "protocol=udp";
$lines[] = "bind=0.0.0.0:5060";
$lines[] = "";

for ($i = 1; $i <= 43; $i++) {
    $suffix = str_pad($i, 2, '0', STR_PAD_LEFT);
    $username = "880170" . $suffix;
    $password = "CP88017@!#@" . $suffix;
    $webrtcUser = "10" . str_pad($i, 2, '0', STR_PAD_LEFT); // 1001, 1002... wait. 1001 is 4 digits. if i=1, 1001. if i=43, 1043.
    // My previous code: $webrtcUser = "10" . $suffix; if suffix is 2 digits (01), "1001". Correct.
    // If i=43, "1043". Correct.
    
    // TRUNK
    $lines[] = "; --- Agent $i ($webrtcUser -> $username) ---";
    $lines[] = "[$username]"; // Auth
    $lines[] = "type=auth";
    $lines[] = "auth_type=userpass";
    $lines[] = "password=$password";
    $lines[] = "username=$username";
    $lines[] = "realm=cp88017.ity.vn";
    $lines[] = "";
    
    $lines[] = "[reg-$username]"; // Registration
    $lines[] = "type=registration";
    $lines[] = "outbound_auth=$username";
    $lines[] = "server_uri=sip:vpbx-php.ity.com.vn";
    $lines[] = "client_uri=sip:$username@vpbx-php.ity.com.vn";
    $lines[] = "contact_user=$username";
    $lines[] = "retry_interval=60";
    $lines[] = "";
    
    $lines[] = "[aor-$username]"; // AOR
    $lines[] = "type=aor";
    $lines[] = "contact=sip:vpbx-php.ity.com.vn";
    $lines[] = "qualify_frequency=60";
    $lines[] = "";
    
    $lines[] = "[trunk-$username]"; // Endpoint
    $lines[] = "type=endpoint";
    $lines[] = "transport=transport-udp";
    $lines[] = "context=from-provider";
    $lines[] = "disallow=all";
    $lines[] = "allow=alaw,ulaw,gsm";
    $lines[] = "outbound_auth=$username";
    $lines[] = "aors=aor-$username";
    $lines[] = "direct_media=no";
    $lines[] = "rtp_symmetric=yes";
    $lines[] = "force_rport=yes";
    $lines[] = "rewrite_contact=yes";
    $lines[] = "from_domain=cp88017.ity.vn";
    $lines[] = "from_user=$username";
    $lines[] = "";

    // WEBRTC ENDPOINT
    $lines[] = "[$webrtcUser]";
    $lines[] = "type=endpoint";
    $lines[] = "transport=transport-ws";
    $lines[] = "context=from-internal";
    $lines[] = "disallow=all";
    $lines[] = "allow=opus,ulaw,alaw";
    $lines[] = "aors=$webrtcUser";
    $lines[] = "auth=$webrtcUser";
    $lines[] = "dtls_auto_generate_cert=yes";
    $lines[] = "webrtc=yes";
    $lines[] = "use_avpf=yes";
    $lines[] = "media_encryption=dtls";
    $lines[] = "dtls_verify=fingerprint";
    $lines[] = "dtls_setup=actpass";
    $lines[] = "ice_support=yes";
    $lines[] = "media_use_received_transport=yes";
    $lines[] = "rtp_symmetric=yes";
    $lines[] = "rewrite_contact=yes";
    $lines[] = "force_rport=yes";
    $lines[] = "callerid=Agent $i <$username>";
    $lines[] = "";
    
    $lines[] = "[$webrtcUser]"; // Auth
    $lines[] = "type=auth";
    $lines[] = "auth_type=userpass";
    $lines[] = "password=webrtc_secret";
    $lines[] = "username=$webrtcUser";
    $lines[] = "";
    
    $lines[] = "[$webrtcUser]"; // AOR
    $lines[] = "type=aor";
    $lines[] = "max_contacts=2";
    $lines[] = "remove_existing=yes";
    $lines[] = "";
}

echo implode("\n", $lines);
