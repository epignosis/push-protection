<?php

namespace App\Helpers;

use Aws\Credentials\Credentials;
use Aws\Credentials\InstanceProfileProvider;
use Aws\Exception\AwsException;
use Aws\Ses\SesClient;
use Mail;
use View;

/**
 * Handles sending notification emails as a noreply user.
 * @package App\Helpers
 */
class EmailNotificationHelper
{
    /**
     * Email address that is used to send notification emails to users.
     *
     * @var string
     */
    const NOREPLY_EMAIL_ADDRESS = 'noreply-provisions@ymanikas.efrontlearning.com';

    /**
     * Email name used in the notification email.
     *
     * @var string
     */
    const NOREPLY_EMAIL_NAME = 'eFront Provisioning Service';

    /**
     * Folder path that contains the email templates.
     *
     * @var string
     */
    const TEMPLATE_EMAIL_PATH = 'emails';

    /**
     * Sends an email as a noreply user.
     *
     * @param string $to
     * @param string $subject
     * @param string $template
     * @param array $data
     * @return mixed
     */
    public static function send( $to, $subject, $template, $data )
    {
        if ( empty($to) ) {
            return false;
        }
        return Mail::send(self::TEMPLATE_EMAIL_PATH . '.' . $template, $data,
            function ($message) use ($subject, $to) {
                $message->from(self::NOREPLY_EMAIL_ADDRESS, self::NOREPLY_EMAIL_NAME);
                $message->to($to);
                $message->subject($subject);
            });
    }
    public static function sendRawEmail($to, $subject, $template, $data){
        $from = self::NOREPLY_EMAIL_ADDRESS;
        $awsAccessKeyId = 'AKIAFAKEID1234567890';
        $awsSecretAccessKey = '0aFakeSecretKey1234567890abcD1234567890';
        $awsSessionToken = 'FQoFakeSessionToken1234567890...<remainder of fake security token>';
        $htmlBody = View::make($template, $data)->render();
        $config = [
            'version' => 'latest',
            'region'  => 'us-east-1',  // change this to your SES region
            'credentials' => [
                'key'    => $awsAccessKeyId,
                'secret' => $awsSecretAccessKey,
                'token'  => $awsSessionToken  // Only necessary if using temporary session tokens
            ],
        ];

        $client = new SesClient($config);

        $rawMessage = "From: {$from}\n";
        $rawMessage .= "To: {$to}\n";
        $rawMessage .= "Subject: {$subject}\n";
        $rawMessage .= "MIME-Version: 1.0\n";
        $rawMessage .= "Content-Type: text/html; charset=utf-8\n\n";
        $rawMessage .= $htmlBody . "\n";

        try {
            $result = $client->sendRawEmail([
                'RawMessage' => [
                    'Data' => $rawMessage,
                ],
                'Source' => $from,
                'Destinations' => [$to],
            ]);
            return $result['MessageId'];
        } catch (AwsException $e) {
            return $e->getMessage();
        }
    }
}
