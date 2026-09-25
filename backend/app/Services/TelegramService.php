<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\SiteSetting;
use App\Enums\UserRole;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TelegramService
{
    /**
     * Get admin and superadmin Telegram IDs directly from the database.
     *
     * @return array<string>
     */
    public static function getAdminChatIds(): array
    {
        return User::whereIn('role', [UserRole::Admin, UserRole::SuperAdmin])
            ->whereNotNull('telegram_id')
            ->pluck('telegram_id')
            ->map(fn($id) => trim((string) $id))
            ->filter(fn($id) => $id !== '')
            ->unique()
            ->values()
            ->toArray();
    }


    /**
     * Get Telegram group chat IDs configured from the environment.
     *
     * @return array<string>
     */
    public static function getGroupChatIds(): array
    {
        $groupIds = config('telegram.group_ids', []);
        if (!is_array($groupIds)) {
            $groupIds = array_filter(array_map('trim', explode(',', (string) $groupIds)));
        }

        return array_values(array_unique(array_filter(array_map(fn($id) => trim((string) $id), $groupIds), fn($id) => $id !== '')));
    }

    /**
     * Get all Telegram recipient chat IDs (Admins/SuperAdmins from DB and Groups from env).
     *
     * @return array<string>
     */
    public static function getAllRecipients(): array
    {
        return array_values(array_unique(array_merge(
            self::getAdminChatIds(),
            self::getGroupChatIds()
        )));
    }

    /**
     * Send a general text message to all configured recipients (or specific chat IDs).
     *
     * @param string $text
     * @param array<string>|null $chatIds
     * @return array<string, mixed>
     */
    public static function sendMessage(string $text, ?array $chatIds = null): array
    {
        $botToken = config('telegram.bot_token');
        if (empty($botToken)) {
            Log::warning('Telegram bot token is not configured, skipping sendMessage');
            return [];
        }

        $recipients = $chatIds ?? self::getAllRecipients();
        $responses = [];

        foreach ($recipients as $chatId) {
            try {
                $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);

                $responses[$chatId] = [
                    'status' => $response->status(),
                    'ok' => $response->json('ok') ?? false,
                    'description' => $response->json('description'),
                ];
            } catch (\Exception $e) {
                Log::error('Failed to send Telegram message to chat_id ' . $chatId, [
                    'error' => $e->getMessage(),
                ]);
                $responses[$chatId] = [
                    'status' => 500,
                    'ok' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $responses;
    }

    public static function notifyAdminNewOrder(Order $order, bool $isManual = false): void
    {
        self::sendOrderNotification($order, $isManual ? 'manual' : 'new');
    }

    public static function notifyAdminOrderUpdated(Order $order, string $changedByName = 'Admin'): void
    {
        self::sendOrderNotification($order, 'updated', $changedByName);
    }

    public static function sendOrderNotification(Order $order, string $type = 'new', string $changedByName = 'Admin'): void
    {
        try {
            $botToken = config('telegram.bot_token');

            if (empty($botToken)) {
                Log::warning('Telegram bot token is not configured, skipping order notification');
                return;
            }

            // Fetch admin IDs and group IDs
            $adminIds = self::getAdminChatIds();
            $groupIds = self::getGroupChatIds();
            $recipients = self::getAllRecipients();

            if (empty($recipients)) {
                Log::warning('No Telegram recipients (admins or group IDs) found for order notifications');
                return;
            }

            $order->loadMissing(['creator', 'user', 'address', 'items.product']);

            if ($type === 'manual') {
                $header = "🛍️ <b>NEW ORDER CREATED (MANUAL)!</b>";
            } elseif ($type === 'updated') {
                $header = "✏️ <b>ORDER UPDATED!</b>";
            } else {
                $header = "🛍️ <b>NEW ORDER RECEIVED!</b>";
            }

            $orderNumber = $order->order_number;
            $orderDate = $order->created_at ? $order->created_at->format('M d, Y H:i:s') : now()->format('M d, Y H:i:s');
            $customerName = $order->user?->name ?? 'Guest';
            $customerPhone = $order->user?->phone ?? $order->address?->phone;
            $customerEmail = $order->user?->email;

            $statusStr = is_object($order->status) ? $order->status->value : (string) $order->status;
            $paymentStatusStr = is_object($order->payment_status) ? $order->payment_status->value : (string) $order->payment_status;

            $paymentMethod = $order->payment_method ? (is_object($order->payment_method) ? $order->payment_method->value : (string) $order->payment_method) : null;

            // Load Payment Receipt file bytes if available
            $receiptBytes = null;
            $receiptFileName = 'payment_receipt.jpg';
            if ($order->payment_receipt && Storage::disk('public')->exists($order->payment_receipt)) {
                $receiptBytes = Storage::disk('public')->get($order->payment_receipt);
                $receiptFileName = basename($order->payment_receipt);
            }

            $adminNote = $order->admin_note;
            $customerNote = $order->customer_note;
            $createdBy = $order->creator?->name ?? ($order->user?->name ?? 'Self-Service');

            // Build main details section
            $lines = [
                "{$header}",
                "📋 <b>Order:</b> #{$orderNumber}",
                "⏰ <b>Date:</b> {$orderDate}",
                "👤 <b>Customer:</b> " . htmlspecialchars($customerName),
            ];

            if ($customerPhone) {
                $lines[] = "📞 <b>Phone:</b> " . htmlspecialchars($customerPhone);
            }
            if ($customerEmail) {
                $lines[] = "✉️ <b>Email:</b> " . htmlspecialchars($customerEmail);
            }

            $lines[] = "💰 <b>Total:</b> $" . number_format($order->total, 2);
            $lines[] = "📦 <b>Status:</b> " . ucfirst($statusStr);
            $lines[] = "💳 <b>Payment:</b> " . ucfirst($paymentStatusStr);

            if ($paymentMethod) {
                $lines[] = "💳 <b>Payment Method:</b> " . ucfirst($paymentMethod);
            }
            if ($adminNote) {
                $lines[] = "📝 <b>Admin Note:</b> " . htmlspecialchars($adminNote);
            }
            if ($customerNote) {
                $lines[] = "📝 <b>Customer Note:</b> " . htmlspecialchars($customerNote);
            }
            $lines[] = "👤 <b>Created By:</b> " . htmlspecialchars($createdBy);
            if ($type === 'updated') {
                $lines[] = "✏️ <b>Updated By:</b> " . htmlspecialchars($changedByName);
            }

            // Build address section
            $address = $order->address;
            $addressSection = "";
            if ($address) {
                $addrLines = [];
                if ($address->name) $addrLines[] = htmlspecialchars($address->name);
                if ($address->label) $addrLines[] = "<b>Label:</b> " . htmlspecialchars($address->label);
                if ($address->phone) $addrLines[] = "<b>Phone:</b> " . htmlspecialchars($address->phone);
                if ($address->full_address) $addrLines[] = htmlspecialchars($address->full_address);

                $addressSection = "📍 <b>Shipping Address:</b>\n" . implode("\n", $addrLines);
                if ($address->latitude && $address->longitude) {
                    $addressSection .= "\n🗺️ <a href='https://www.google.com/maps?q={$address->latitude},{$address->longitude}'>View on Google Maps</a>";
                }
            } else {
                $addressSection = "📍 <b>Shipping Address:</b>\nNo shipping address";
            }

            $message = implode("\n", $lines) . "\n\n" . $addressSection;

            // Generate A4 Receipt PDF for attachment
            $pdfBytes = null;
            try {
                $company = [
                    'name' => SiteSetting::get('site_name', 'Unknown'),
                    'email' => SiteSetting::get('contact_email', ''),
                    'phone' => SiteSetting::get('contact_phone', ''),
                    'address_line1' => SiteSetting::get('contact_address', ''),
                    'address_line2' => null,
                ];

                $pdf = Pdf::loadView('invoices.order', [
                    'order' => $order,
                    'company' => array_filter($company),
                ])->setPaper('A4');

                $pdfBytes = $pdf->output();
            } catch (\Exception $pdfEx) {
                Log::error('Failed to generate A4 PDF for Telegram notification', [
                    'error' => $pdfEx->getMessage(),
                    'order_id' => $order->id,
                ]);
            }

            // Prepare caption (Telegram allows up to 1024 characters for captions)
            $needsSeparateMessage = mb_strlen($message) > 1024;
            $shortCaption = "📄 Details for Order #{$orderNumber}";

            // Send to all recipients (admins and groups)
            Log::info('Sending order notifications via Telegram', [
                'order_number' => $order->order_number,
                'type' => $type,
                'admin_ids' => $adminIds,
                'group_ids' => $groupIds,
                'total_recipients' => count($recipients),
                'has_pdf' => !empty($pdfBytes),
                'has_receipt_img' => !empty($receiptBytes),
            ]);

            foreach ($recipients as $chatId) {
                try {
                    $sentFullText = false;

                    // 1. If message is too long, we MUST send it as a separate text message first
                    if ($needsSeparateMessage && ($receiptBytes || $pdfBytes)) {
                        $textResp = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                            'chat_id' => $chatId,
                            'text' => $message,
                            'parse_mode' => 'HTML',
                            'disable_web_page_preview' => true,
                        ]);

                        Log::info('Telegram API sendMessage (long text) Response for Order', [
                            'chat_id' => $chatId,
                            'order_number' => $order->order_number,
                            'status_code' => $textResp->status(),
                            'ok' => $textResp->json('ok') ?? false,
                            'description' => $textResp->json('description'),
                        ]);

                        $sentFullText = true;
                    }

                    $captionToSend = $sentFullText ? $shortCaption : $message;

                    if ($receiptBytes) {
                        // 2. Combine Payment Receipt Photo + Text (or short caption)
                        $photoResponse = Http::attach('photo', $receiptBytes, $receiptFileName)
                            ->post("https://api.telegram.org/bot{$botToken}/sendPhoto", [
                                'chat_id' => $chatId,
                                'caption' => $captionToSend,
                                'parse_mode' => 'HTML',
                            ]);

                        Log::info('Telegram API sendPhoto Response for Order', [
                            'chat_id' => $chatId,
                            'order_number' => $order->order_number,
                            'status_code' => $photoResponse->status(),
                            'ok' => $photoResponse->json('ok') ?? false,
                            'description' => $photoResponse->json('description'),
                        ]);

                        // Send attached A4 Receipt PDF document (always separate if we have photo)
                        if ($pdfBytes) {
                            $fileName = "Receipt-A4-{$order->order_number}.pdf";
                            $docResponse = Http::attach('document', $pdfBytes, $fileName)
                                ->post("https://api.telegram.org/bot{$botToken}/sendDocument", [
                                    'chat_id' => $chatId,
                                    'caption' => "📄 A4 Receipt PDF (#{$order->order_number})",
                                ]);

                            Log::info('Telegram API sendDocument Response for Order A4 Receipt (with photo)', [
                                'chat_id' => $chatId,
                                'order_number' => $order->order_number,
                                'status_code' => $docResponse->status(),
                                'ok' => $docResponse->json('ok') ?? false,
                                'description' => $docResponse->json('description'),
                            ]);
                        }
                    } elseif ($pdfBytes) {
                        // 3. Combine A4 PDF Document + Text (or short caption)
                        $fileName = "Receipt-A4-{$order->order_number}.pdf";
                        $docResponse = Http::attach('document', $pdfBytes, $fileName)
                            ->post("https://api.telegram.org/bot{$botToken}/sendDocument", [
                                'chat_id' => $chatId,
                                'caption' => $captionToSend,
                                'parse_mode' => 'HTML',
                            ]);

                        Log::info('Telegram API sendDocument Response for Order A4 Receipt', [
                            'chat_id' => $chatId,
                            'order_number' => $order->order_number,
                            'status_code' => $docResponse->status(),
                            'ok' => $docResponse->json('ok') ?? false,
                            'description' => $docResponse->json('description'),
                        ]);
                    } else {
                        // 4. Send text message only (if no attachments, or we haven't sent it yet)
                        if (!$sentFullText) {
                            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                                'chat_id' => $chatId,
                                'text' => $message,
                                'parse_mode' => 'HTML',
                                'disable_web_page_preview' => true,
                            ]);

                            Log::info('Telegram API sendMessage Response for Order', [
                                'chat_id' => $chatId,
                                'order_number' => $order->order_number,
                                'status_code' => $response->status(),
                                'ok' => $response->json('ok') ?? false,
                                'description' => $response->json('description'),
                            ]);
                        }
                    }
                } catch (\Exception $chatEx) {
                    Log::error('Failed to send Telegram message to chat_id ' . $chatId, [
                        'order_id' => $order->id,
                        'chat_id' => $chatId,
                        'error' => $chatEx->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send order notification via Telegram', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
            ]);
        }
    }
}
