<?php
$pageTitle = 'Upload Rules';
require __DIR__ . '/header.php';
?>

<h1 class="page-title">Upload Rules</h1>

<div class="rules-box">
    <p>To keep OpenGifs useful for everyone, please follow these rules.</p>

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
        <li><span class="rule-no">✗</span> <strong>NSFW / Adult content</strong></li>
        <li><span class="rule-no">✗</span> <strong>Violence and gore</strong></li>
        <li><span class="rule-no">✗</span> <strong>Harassment and hate speech</strong></li>
        <li><span class="rule-no">✗</span> <strong>Copyrighted material</strong></li>
        <li><span class="rule-no">✗</span> <strong>Malware or phishing</strong></li>
        <li><span class="rule-no">✗</span> <strong>Illegal content</strong></li>
    </ul>

    <h2>Technical Limits</h2>
    <ul>
        <li>Max file size: <strong>32 MB</strong></li>
        <li>Only <strong>GIF</strong> format</li>
    </ul>

    <h2>Enforcement</h2>
    <p>Violations result in content removal. Repeat offenders may be blocked.</p>
</div>

<?php require __DIR__ . '/footer.php'; ?>
