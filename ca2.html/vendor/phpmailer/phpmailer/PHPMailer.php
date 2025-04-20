<?php
namespace PHPMailer\PHPMailer;

class PHPMailer
{
    public $CharSet = 'UTF-8';
    public $Host = 'smtp.gmail.com';
    public $Port = 587;
    public $SMTPAuth = true;
    public $SMTPSecure = 'tls';
    public $Username = '';
    public $Password = '';
    public $From = '';
    public $FromName = '';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $ErrorInfo = '';
    private $to = array();
    private $isSmtp = false;
    
    public function isSMTP() {
        $this->isSmtp = true;
        return $this;
    }
    
    public function setFrom($address, $name = '') {
        $this->From = $address;
        $this->FromName = $name;
        return $this;
    }
    
    public function addAddress($address) {
        $this->to[] = $address;
        return $this;
    }
    
    public function send() {
        if ($this->isSmtp) {
            if (empty($this->Username) || empty($this->Password)) {
                error_log("SMTP credentials check - Username: " . (empty($this->Username) ? "empty" : "set") . 
                         ", Password: " . (empty($this->Password) ? "empty" : "set"));
                throw new Exception('SMTP credentials are required');
            }

            try {
                error_log("Attempting SMTP connection to {$this->Host}:{$this->Port}");
                
                // First establish a regular connection
                $smtp = @fsockopen(
                    $this->Host,
                    $this->Port,
                    $errno,
                    $errstr,
                    30
                );

                if (!$smtp) {
                    throw new Exception("SMTP Connection failed: $errstr ($errno)");
                }

                error_log("Initial SMTP connection established");

                // Read greeting
                $response = $this->getResponse($smtp);
                error_log("Server greeting: " . trim($response));

                // Send EHLO
                $response = $this->sendCommand($smtp, "EHLO " . $this->Host);
                error_log("EHLO response: " . trim($response));

                // Start TLS
                $response = $this->sendCommand($smtp, "STARTTLS");
                error_log("STARTTLS response: " . trim($response));
                
                // Enable crypto
                if (!stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    error_log("Failed to enable TLS encryption");
                    throw new Exception("Failed to enable TLS encryption");
                }

                error_log("TLS encryption enabled successfully");

                // Send EHLO again after TLS
                $response = $this->sendCommand($smtp, "EHLO " . $this->Host);
                error_log("EHLO (after TLS) response: " . trim($response));

                // Authenticate
                $response = $this->sendCommand($smtp, "AUTH LOGIN");
                error_log("AUTH LOGIN response: " . trim($response));
                
                $response = $this->sendCommand($smtp, base64_encode($this->Username));
                error_log("Username response: " . trim($response));
                
                $response = $this->sendCommand($smtp, base64_encode($this->Password));
                error_log("Password response: " . trim($response));

                // Send From
                $response = $this->sendCommand($smtp, "MAIL FROM:<{$this->From}>");
                error_log("MAIL FROM response: " . trim($response));

                // Send To
                foreach ($this->to as $recipient) {
                    $response = $this->sendCommand($smtp, "RCPT TO:<$recipient>");
                    error_log("RCPT TO response: " . trim($response));
                }

                // Send Data
                $response = $this->sendCommand($smtp, "DATA");
                error_log("DATA response: " . trim($response));

                // Prepare headers
                $headers = array(
                    'MIME-Version: 1.0',
                    'Content-Type: text/html; charset=' . $this->CharSet,
                    'From: ' . $this->FromName . ' <' . $this->From . '>',
                    'To: ' . implode(', ', $this->to),
                    'Subject: ' . $this->Subject,
                    'Date: ' . date('r'),
                    'X-Mailer: PHP/' . phpversion(),
                    ''
                );

                // Send headers and body
                $message = implode("\r\n", $headers) . "\r\n" . $this->Body . "\r\n.\r\n";
                fwrite($smtp, $message);
                $response = $this->getResponse($smtp);
                error_log("Message response: " . trim($response));

                // Quit
                $response = $this->sendCommand($smtp, "QUIT");
                error_log("QUIT response: " . trim($response));
                
                fclose($smtp);
                error_log("SMTP connection closed successfully");

                return true;

            } catch (Exception $e) {
                $this->ErrorInfo = $e->getMessage();
                error_log("SMTP Error: " . $e->getMessage());
                if (isset($smtp) && is_resource($smtp)) {
                    fclose($smtp);
                }
                throw $e;
            }
        } else {
            // Use regular mail() function as fallback
            $headers = array(
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=' . $this->CharSet,
                'From: ' . $this->FromName . ' <' . $this->From . '>',
                'X-Mailer: PHP/' . phpversion()
            );
            
            foreach ($this->to as $recipient) {
                $result = mail($recipient, $this->Subject, $this->Body, implode("\r\n", $headers));
                if (!$result) {
                    $this->ErrorInfo = error_get_last()['message'];
                    return false;
                }
            }
            
            return true;
        }
    }

    private function sendCommand($smtp, $command) {
        error_log("Sending command: " . $command);
        fwrite($smtp, $command . "\r\n");
        $response = $this->getResponse($smtp);
        error_log("Response: " . trim($response));
        return $response;
    }

    private function getResponse($smtp) {
        $response = '';
        while ($line = fgets($smtp, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        return $response;
    }
}
