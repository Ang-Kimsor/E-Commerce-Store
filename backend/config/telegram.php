<?php

$parseTelegramIds = function ($value): array {
    if (empty($value)) {
        return [];
    }

    if (is_array($value)) {
        return array_values(array_filter(array_map('trim', $value)));
    }

    $raw = trim((string) $value);

    // Support JSON array format, e.g. ["-5182843964", "-1001234567"]
    if (str_starts_with($raw, '[') && str_ends_with($raw, ']')) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('trim', $decoded)));
        }
    }

    // Support comma-separated format, e.g. "-5182843964, -1001234567"
    return array_values(array_filter(
        array_map('trim', explode(',', $raw)),
        fn($item) => $item !== ''
    ));
};

return [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'init_data_ttl' => env('TELEGRAM_INIT_DATA_TTL', 86400),
    'group_ids' => $parseTelegramIds(env('TELEGRAM_GROUP_IDS', env('TELEGRAM_GROUP_ID', ''))),
];
