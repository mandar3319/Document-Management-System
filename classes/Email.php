<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

class Mailer
{
    private $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        $this->mail->isSMTP();
        $this->mail->Host       = 'smtp.gmail.com';
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = 'vivekjadhav6088@gmail.com';         
        $this->mail->Password   = 'szkh gjkj iqze klmr';            
        $this->mail->SMTPSecure = 'ssl';
        $this->mail->Port       = 465;

        $this->mail->setFrom('vivekjadhav6088@gmail.com', 'Vivek Jadhav'); 
        $this->mail->isHTML(true);
    }
    

    public function sendCompanyCreatedEmail($toEmail, $companyName)
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($toEmail);
            $this->mail->Subject = 'Company Created Successfully';
            $this->mail->Body    = "
                Hello,<br><br>
                Your company <b>{$companyName}</b> has been successfully created in our DMS portal.<br><br>
                Regards,<br>DMS Team
            ";

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }

public function userCreatedEmail($toEmail, $userName, $companyName, $loginUrl, $plainPassword)
{
    try {
        $this->mail->clearAddresses();
        $this->mail->addAddress($toEmail);
        $this->mail->Subject = 'Your account is ready - ' . $companyName;

        $timestamp = date("Y-m-d H:i:s");

        $this->mail->isHTML(true);
        $this->mail->Body = "
            Hello <b>{$userName}</b>,<br><br>
            Your account has been successfully created under the company <b>{$companyName}</b> in our Document Management System.<br><br>

            <b>Login Details:</b><br>
            Email: <code>{$toEmail}</code><br>
            Password: <code>{$plainPassword}</code><br><br>

            You can log in here: <a href='{$loginUrl}' target='_blank'>{$loginUrl}</a><br><br>

            <b>Note:</b> Please change your password after logging in for the first time.<br>
            <b>Time:</b> {$timestamp}<br><br>

            Regards,<br>
            <b>DMS Team</b>
        ";

        $this->mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$this->mail->ErrorInfo}");
        return false;
    }
}


}
