<?php

class SMTPMailer {
    private $host;
    private $port;
    private $username;
    private $password;
    private $fromEmail;
    private $fromName;

    public function __construct($host, $port, $username, $password, $fromEmail, $fromName = "AutoChek") {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    public function send($to, $subject, $body) {
        $msg = "";
        
        // Connect to SMTP Server
        $socket = fsockopen($this->host, $this->port, $errno, $errstr, 15);
        if (!$socket) {
            return ["success" => false, "error" => "Could not connect to SMTP host. $errstr ($errno)"];
        }

        $this->readResponse($socket); // banner

        // HELO/EHLO
        $this->sendCommand($socket, "EHLO " . gethostname());

        // STARTTLS
        $this->sendCommand($socket, "STARTTLS");
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
             return ["success" => false, "error" => "TLS negotiation failed"];
        }
        $this->sendCommand($socket, "EHLO " . gethostname());

        // AUTH LOGIN
        $this->sendCommand($socket, "AUTH LOGIN");
        $this->sendCommand($socket, base64_encode($this->username));
        $this->sendCommand($socket, base64_encode($this->password));

        // Mail From
        $this->sendCommand($socket, "MAIL FROM: <" . $this->fromEmail . ">");
        
        // Rcpt To
        $this->sendCommand($socket, "RCPT TO: <" . $to . ">");
        
        // Data
        $this->sendCommand($socket, "DATA");
        
        // Headers & Body
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . $this->fromName . " <" . $this->fromEmail . ">\r\n";
        $headers .= "To: <" . $to . ">\r\n";
        $headers .= "Subject: " . $subject . "\r\n";
        
        fwrite($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
        $response = $this->readResponse($socket);

        // QUIT
        $this->sendCommand($socket, "QUIT");
        fclose($socket);
        
        if (substr($response, 0, 3) == "250") {
             return ["success" => true];
        } else {
             return ["success" => false, "error" => "Email rejected: $response"];
        }
    }

    private function sendCommand($socket, $cmd) {
        fwrite($socket, $cmd . "\r\n");
        return $this->readResponse($socket);
    }

    private function readResponse($socket) {
        $response = "";
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ") { break; }
        }
        return $response;
    }
}
?>
