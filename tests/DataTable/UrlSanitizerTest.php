<?php

use KoreUi\DataTable\Support\UrlSanitizer;

it('allows http and https urls', function () {
    expect(UrlSanitizer::sanitize('https://example.com/path'))->toBe('https://example.com/path')
        ->and(UrlSanitizer::sanitize('http://example.com'))->toBe('http://example.com');
});

it('allows relative paths, queries and anchors', function () {
    expect(UrlSanitizer::sanitize('/dashboard'))->toBe('/dashboard')
        ->and(UrlSanitizer::sanitize('?q=1'))->toBe('?q=1')
        ->and(UrlSanitizer::sanitize('#section'))->toBe('#section');
});

it('blocks the javascript scheme', function () {
    expect(UrlSanitizer::sanitize('javascript:alert(1)'))->toBeNull();
});

it('blocks the data scheme', function () {
    expect(UrlSanitizer::sanitize('data:text/html,<script>alert(1)</script>'))->toBeNull();
});

it('blocks scheme-relative urls that point at an arbitrary host (open redirect)', function () {
    expect(UrlSanitizer::sanitize('//evil.com/phish'))->toBeNull();
});

it('strips control characters that smuggle a dangerous scheme', function () {
    // Browsers ignore embedded tabs/newlines: "java\tscript:" runs as javascript:
    expect(UrlSanitizer::sanitize("java\tscript:alert(1)"))->toBeNull()
        ->and(UrlSanitizer::sanitize("java\nscript:alert(1)"))->toBeNull();
});

it('preserves null and empty input', function () {
    expect(UrlSanitizer::sanitize(null))->toBeNull()
        ->and(UrlSanitizer::sanitize(''))->toBe('');
});
