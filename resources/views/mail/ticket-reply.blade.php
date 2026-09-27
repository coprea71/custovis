{{-- Table layout with inline styles: the only markup Outlook, Gmail and Apple Mail render alike. --}}
@php
    $logoSrc ??= isset($message) && $message instanceof \Illuminate\Mail\Message ? $layout->embeddedLogo($message) : null;
    $font = $layout->fontStack();
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $layout->header_text ?: $layout->team?->name }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5f7;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f5f7;">
    <tr>
        <td align="center" style="padding:24px 12px;">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background-color:#ffffff;border-top:4px solid {{ $layout->accent_color }};">
                @if ($logoSrc || $layout->header_text)
                    <tr>
                        <td style="padding:24px 32px 0 32px;font-family:{{ $font }};font-size:18px;font-weight:bold;color:{{ $layout->accent_color }};">
                            @if ($logoSrc)
                                <img src="{{ $logoSrc }}" alt="{{ $layout->header_text ?: $layout->team?->name }}" style="display:block;max-width:200px;max-height:80px;border:0;">
                            @else
                                {{ $layout->header_text }}
                            @endif
                        </td>
                    </tr>
                @endif
                <tr>
                    <td style="padding:24px 32px;font-family:{{ $font }};font-size:15px;line-height:1.6;color:#1f2933;">
                        {!! $body !!}
                        @if ($signature !== '')
                            <p style="margin:24px 0 0 0;">{!! nl2br(e($signature)) !!}</p>
                        @endif
                    </td>
                </tr>
                @if ($layout->footer_text)
                    <tr>
                        <td style="padding:16px 32px 24px 32px;border-top:1px solid #e4e7eb;font-family:{{ $font }};font-size:12px;line-height:1.5;color:#616e7c;">
                            {!! nl2br(e($layout->footer_text)) !!}
                        </td>
                    </tr>
                @endif
            </table>
        </td>
    </tr>
</table>
</body>
</html>
