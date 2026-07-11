@extends('layouts.app')

@section('title', 'Upload Rules')

@section('content')
    <h1 class="page-title">Upload Rules</h1>

    <div class="rules-box">
        <p>To keep OpenGifs useful and safe for everyone, please follow these rules when uploading content.</p>

        <h2>✅ Allowed</h2>
        <ul>
            <li><span class="rule-yes">✓</span> Funny and memes</li>
            <li><span class="rule-yes">✓</span> Reaction GIFs</li>
            <li><span class="rule-yes">✓</span> Educational content</li>
            <li><span class="rule-yes">✓</span> Art and animations</li>
            <li><span class="rule-yes">✓</span> Gaming clips</li>
            <li><span class="rule-yes">✓</span> Sports highlights</li>
            <li><span class="rule-yes">✓</span> Any safe-for-work content</li>
        </ul>

        <h2>❌ Not Allowed</h2>
        <ul>
            <li><span class="rule-no">✗</span> <strong>NSFW / Adult content</strong> — nudity, sexual content, pornography of any kind</li>
            <li><span class="rule-no">✗</span> <strong>Violence and gore</strong> — graphic violence, animal cruelty, self-harm</li>
            <li><span class="rule-no">✗</span> <strong>Harassment and hate speech</strong> — content targeting individuals or groups</li>
            <li><span class="rule-no">✗</span> <strong>Copyrighted material</strong> — content you do not own the rights to</li>
            <li><span class="rule-no">✗</span> <strong>Malware or phishing</strong> — any content designed to deceive or harm</li>
            <li><span class="rule-no">✗</span> <strong>Illegal content</strong> — anything prohibited by law</li>
        </ul>

        <h2>Technical Limits</h2>
        <ul>
            <li>Maximum file size: <strong>32 MB</strong></li>
            <li>Only <strong>GIF</strong> format is accepted</li>
        </ul>

        <h2>Enforcement</h2>
        <p>
            Violations will result in content removal without notice.
            Repeat offenders may be blocked from uploading.
            If you see inappropriate content, please report it by
            <a href="mailto:abuse@opengifs.com">contacting us</a>.
        </p>

        <p style="margin-top:20px;color:#888;font-size:13px;">
            Last updated: July 2026
        </p>
    </div>
@endsection
