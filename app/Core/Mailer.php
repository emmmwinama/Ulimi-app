<?php

declare(strict_types=1);

namespace App\Core;

use App\Support\Dates;
use App\Support\Ulid;
use Throwable;

/**
 * Transactional email. Messages are rendered from templates under
 * app/Views/emails, written to the `mail_queue` table, and flushed
 * opportunistically after the HTTP response (no cron on the target host).
 *
 * Drivers:
 *   - "smtp": send via the vendored PHPMailer over the configured SMTP server.
 *   - "log":  write the rendered message to storage/logs/mail-YYYY-MM-DD.log
 *             and mark it sent. Used locally and as a safe default.
 */
final class Mailer
{
    /**
     * Queue an email for delivery.
     *
     * @param array<string,mixed> $data  template variables
     */
    public static function queue(
        string $toEmail,
        string $toName,
        string $subject,
        string $template,
        array $data = [],
    ): void {
        $html = View::instance()->render('emails/' . $template, $data + [
            'subject'  => $subject,
            'appName'  => (string) Config::get('app.name', 'AgriVault'),
            'appUrl'   => (string) Config::get('app.url', ''),
        ]);

        Database::instance()->insert('mail_queue', [
            'id'         => Ulid::generate(),
            'to_email'   => $toEmail,
            'to_name'    => $toName,
            'subject'    => $subject,
            'html_body'  => $html,
            'text_body'  => self::htmlToText($html),
            'status'     => 'pending',
            'attempts'   => 0,
            'created_at' => Dates::nowUtc(),
        ]);
    }

    /**
     * Attempt delivery of up to $limit pending messages. Safe to call on every
     * request; it no-ops quickly when the queue is empty.
     */
    public static function flush(int $limit = 10): void
    {
        $db = Database::instance();

        $pending = $db->select(
            'SELECT * FROM mail_queue
             WHERE status = :status AND attempts < 5
             ORDER BY created_at ASC
             LIMIT ' . max(1, min(50, $limit)),
            ['status' => 'pending'],
        );

        foreach ($pending as $message) {
            try {
                self::deliver(
                    (string) $message['to_email'],
                    (string) $message['to_name'],
                    (string) $message['subject'],
                    (string) $message['html_body'],
                    (string) $message['text_body'],
                );
                $db->update('mail_queue', [
                    'status'  => 'sent',
                    'sent_at' => Dates::nowUtc(),
                ], ['id' => $message['id']]);
            } catch (Throwable $e) {
                $attempts = (int) $message['attempts'] + 1;
                $db->update('mail_queue', [
                    'attempts'   => $attempts,
                    'status'     => $attempts >= 5 ? 'failed' : 'pending',
                    'last_error' => substr($e->getMessage(), 0, 500),
                ], ['id' => $message['id']]);
                Logger::instance()->warning('Mail delivery failed (attempt {n})', ['n' => $attempts]);
            }
        }
    }

    private static function deliver(
        string $toEmail,
        string $toName,
        string $subject,
        string $html,
        string $text,
    ): void {
        $driver = (string) Config::get('mail.driver', 'log');

        if ($driver === 'log' || !class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            $line = sprintf(
                "[%s] To: %s <%s>\nSubject: %s\n%s\n%s\n",
                date('Y-m-d H:i:s'),
                $toName,
                $toEmail,
                $subject,
                str_repeat('-', 60),
                $text,
            );
            @file_put_contents(
                storage_path('logs/mail-' . date('Y-m-d') . '.log'),
                $line . "\n",
                FILE_APPEND | LOCK_EX,
            );
            return;
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = (string) Config::get('mail.host', '');
        $mail->Port       = (int) Config::get('mail.port', 587);
        $mail->SMTPAuth   = true;
        $mail->Username   = (string) Config::get('mail.username', '');
        $mail->Password   = (string) Config::get('mail.password', '');
        $encryption       = (string) Config::get('mail.encryption', 'tls');
        if ($encryption !== '') {
            $mail->SMTPSecure = $encryption; // 'tls' | 'ssl'
        }
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(
            (string) Config::get('mail.from_email', 'no-reply@localhost'),
            (string) Config::get('mail.from_name', 'AgriVault'),
        );
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body    = $html;
        $mail->AltBody = $text;
        $mail->send();
    }

    private static function htmlToText(string $html): string
    {
        $text = preg_replace('/<(head|style|script)\b[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
        $text = preg_replace('/<(br|\/p|\/div|\/h[1-6]|\/li)\s*>/i', "\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n\s*\n\s*\n+/', "\n\n", $text) ?? $text;
        return trim($text);
    }
}
