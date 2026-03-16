<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScanWebsiteTool extends AbstractTool
{
    public function name(): string
    {
        return 'scan_website';
    }

    public function description(): string
    {
        return 'Scan a website URL to extract its content, structure, navigation, images, colors, and meta information. Use this to analyze competitor sites, recreate existing websites, or gather content from a reference URL.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'url' => [
                    'type' => 'string',
                    'description' => 'The full URL to scan (e.g., https://example.com)',
                ],
                'include_subpages' => [
                    'type' => 'boolean',
                    'description' => 'Whether to also scan linked subpages on the same domain (up to 10). Defaults to false.',
                ],
            ],
            'required' => ['url'],
        ];
    }

    public function execute(array $params): array
    {
        $url = $params['url'] ?? '';
        $includeSubpages = $params['include_subpages'] ?? false;

        if (empty($url)) {
            return $this->error('URL is required.');
        }

        // Ensure URL has protocol
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }

        try {
            // Fetch the main page
            $mainPage = $this->fetchAndParse($url);

            if (!$mainPage['success']) {
                return $this->error('Failed to fetch the website: ' . $mainPage['error']);
            }

            $result = [
                'url' => $url,
                'title' => $mainPage['title'],
                'meta_description' => $mainPage['meta_description'],
                'meta_keywords' => $mainPage['meta_keywords'],
                'headings' => $mainPage['headings'],
                'text_content' => $mainPage['text_content'],
                'navigation' => $mainPage['navigation'],
                'images' => $mainPage['images'],
                'colors' => $mainPage['colors'],
                'fonts' => $mainPage['fonts'],
                'links' => $mainPage['internal_links'],
                'social_links' => $mainPage['social_links'],
                'contact_info' => $mainPage['contact_info'],
            ];

            // Optionally scan subpages
            if ($includeSubpages && !empty($mainPage['internal_links'])) {
                $subpages = [];
                $scannedCount = 0;
                $maxSubpages = 10;

                foreach ($mainPage['internal_links'] as $link) {
                    if ($scannedCount >= $maxSubpages) break;

                    $subUrl = $this->resolveUrl($link['href'], $url);
                    if (!$subUrl || $subUrl === $url) continue;

                    $subPage = $this->fetchAndParse($subUrl);
                    if ($subPage['success']) {
                        $subpages[] = [
                            'url' => $subUrl,
                            'title' => $subPage['title'],
                            'headings' => $subPage['headings'],
                            'text_content' => mb_substr($subPage['text_content'], 0, 2000),
                        ];
                        $scannedCount++;
                    }
                }

                $result['subpages'] = $subpages;
            }

            // Truncate text content to avoid token overflow
            if (strlen($result['text_content']) > 5000) {
                $result['text_content'] = mb_substr($result['text_content'], 0, 5000) . "\n\n[Content truncated — showing first 5000 characters]";
            }

            return $this->success($result, "Successfully scanned {$url}");

        } catch (\Exception $e) {
            Log::error('ScanWebsiteTool error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Failed to scan website: ' . $e->getMessage());
        }
    }

    /**
     * Fetch a URL and parse its HTML content.
     */
    protected function fetchAndParse(string $url): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; GKeysCMS/1.0; +https://gkeyscms.com)',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                ])
                ->get($url);

            if (!$response->successful()) {
                return ['success' => false, 'error' => "HTTP {$response->status()}"];
            }

            $html = $response->body();
            $dom = new \DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR);
            $xpath = new \DOMXPath($dom);

            return [
                'success' => true,
                'title' => $this->extractTitle($xpath),
                'meta_description' => $this->extractMeta($xpath, 'description'),
                'meta_keywords' => $this->extractMeta($xpath, 'keywords'),
                'headings' => $this->extractHeadings($xpath),
                'text_content' => $this->extractTextContent($xpath),
                'navigation' => $this->extractNavigation($xpath),
                'images' => $this->extractImages($xpath, $url),
                'colors' => $this->extractColors($html),
                'fonts' => $this->extractFonts($html),
                'internal_links' => $this->extractInternalLinks($xpath, $url),
                'social_links' => $this->extractSocialLinks($xpath),
                'contact_info' => $this->extractContactInfo($xpath, $html),
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function extractTitle(\DOMXPath $xpath): string
    {
        $nodes = $xpath->query('//title');
        return $nodes->length > 0 ? trim($nodes->item(0)->textContent) : '';
    }

    protected function extractMeta(\DOMXPath $xpath, string $name): string
    {
        $nodes = $xpath->query("//meta[@name='{$name}']/@content");
        if ($nodes->length === 0) {
            $nodes = $xpath->query("//meta[@property='og:{$name}']/@content");
        }
        return $nodes->length > 0 ? trim($nodes->item(0)->textContent) : '';
    }

    protected function extractHeadings(\DOMXPath $xpath): array
    {
        $headings = [];
        for ($level = 1; $level <= 4; $level++) {
            $nodes = $xpath->query("//h{$level}");
            foreach ($nodes as $node) {
                $text = trim($node->textContent);
                if (!empty($text) && strlen($text) < 200) {
                    $headings[] = [
                        'level' => $level,
                        'text' => $text,
                    ];
                }
            }
        }
        return array_slice($headings, 0, 30);
    }

    protected function extractTextContent(\DOMXPath $xpath): string
    {
        // Remove script, style, nav, header, footer elements
        $removeNodes = $xpath->query('//script | //style | //noscript | //iframe');
        foreach ($removeNodes as $node) {
            $node->parentNode->removeChild($node);
        }

        // Get main content areas
        $contentNodes = $xpath->query('//main | //article | //section | //div[@class]');
        $text = '';

        if ($contentNodes->length > 0) {
            foreach ($contentNodes as $node) {
                $nodeText = trim($node->textContent);
                if (strlen($nodeText) > 50) {
                    $text .= $nodeText . "\n\n";
                }
            }
        }

        // Fallback to body
        if (empty(trim($text))) {
            $body = $xpath->query('//body');
            if ($body->length > 0) {
                $text = trim($body->item(0)->textContent);
            }
        }

        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    protected function extractNavigation(\DOMXPath $xpath): array
    {
        $nav = [];
        $navNodes = $xpath->query('//nav//a | //header//a');
        $seen = [];

        foreach ($navNodes as $node) {
            $text = trim($node->textContent);
            $href = $node->getAttribute('href');

            if (empty($text) || strlen($text) > 50 || isset($seen[$text])) continue;
            $seen[$text] = true;

            $nav[] = [
                'label' => $text,
                'href' => $href,
            ];
        }

        return array_slice($nav, 0, 20);
    }

    protected function extractImages(\DOMXPath $xpath, string $baseUrl): array
    {
        $images = [];
        $nodes = $xpath->query('//img[@src]');
        $seen = [];

        foreach ($nodes as $node) {
            $src = $node->getAttribute('src');
            $alt = $node->getAttribute('alt');

            $fullSrc = $this->resolveUrl($src, $baseUrl);
            if (!$fullSrc || isset($seen[$fullSrc])) continue;
            $seen[$fullSrc] = true;

            // Skip tiny images (likely icons/tracking pixels)
            $width = $node->getAttribute('width');
            $height = $node->getAttribute('height');
            if (($width && (int)$width < 50) || ($height && (int)$height < 50)) continue;

            $images[] = [
                'src' => $fullSrc,
                'alt' => $alt ?: '',
            ];
        }

        return array_slice($images, 0, 20);
    }

    protected function extractColors(string $html): array
    {
        $colors = [];

        // Extract hex colors from inline styles and CSS
        preg_match_all('/#([0-9a-fA-F]{3,8})\b/', $html, $matches);
        $hexColors = array_unique($matches[0] ?? []);

        // Extract rgb/rgba colors
        preg_match_all('/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/', $html, $rgbMatches, PREG_SET_ORDER);
        foreach ($rgbMatches as $match) {
            $hex = sprintf('#%02x%02x%02x', $match[1], $match[2], $match[3]);
            $hexColors[] = $hex;
        }

        // Filter out common non-design colors and normalize
        $filtered = [];
        foreach (array_unique($hexColors) as $color) {
            $color = strtolower($color);
            // Skip very common/generic colors
            if (in_array($color, ['#fff', '#ffffff', '#000', '#000000', '#333', '#333333', '#666', '#666666', '#999', '#999999', '#ccc', '#cccccc', '#eee', '#eeeeee', '#f5f5f5', '#fafafa'])) continue;
            $filtered[] = $color;
        }

        return array_slice($filtered, 0, 15);
    }

    protected function extractFonts(string $html): array
    {
        $fonts = [];

        // Extract from Google Fonts links
        preg_match_all('/fonts\.googleapis\.com\/css2?\?family=([^"&]+)/', $html, $matches);
        foreach ($matches[1] ?? [] as $fontParam) {
            $fontNames = explode('|', urldecode($fontParam));
            foreach ($fontNames as $name) {
                $name = preg_replace('/[:+].*/', '', $name);
                $name = str_replace('+', ' ', $name);
                if (!empty(trim($name))) {
                    $fonts[] = trim($name);
                }
            }
        }

        // Extract from font-family declarations
        preg_match_all('/font-family\s*:\s*["\']?([^;"\'}\)]+)/', $html, $matches);
        foreach ($matches[1] ?? [] as $fontDecl) {
            $firstFont = trim(explode(',', $fontDecl)[0]);
            $firstFont = trim($firstFont, "\"' ");
            if (!empty($firstFont) && !in_array(strtolower($firstFont), ['inherit', 'initial', 'unset', 'sans-serif', 'serif', 'monospace', 'cursive', 'fantasy', 'system-ui'])) {
                $fonts[] = $firstFont;
            }
        }

        return array_values(array_unique($fonts));
    }

    protected function extractInternalLinks(\DOMXPath $xpath, string $baseUrl): array
    {
        $links = [];
        $parsed = parse_url($baseUrl);
        $baseDomain = $parsed['host'] ?? '';
        $nodes = $xpath->query('//a[@href]');
        $seen = [];

        foreach ($nodes as $node) {
            $href = $node->getAttribute('href');
            $text = trim($node->textContent);

            $fullUrl = $this->resolveUrl($href, $baseUrl);
            if (!$fullUrl) continue;

            $linkParsed = parse_url($fullUrl);
            $linkDomain = $linkParsed['host'] ?? '';

            // Only internal links
            if ($linkDomain !== $baseDomain) continue;
            if (isset($seen[$fullUrl])) continue;
            $seen[$fullUrl] = true;

            // Skip anchors, javascript, mailto, tel
            if (preg_match('/^(#|javascript:|mailto:|tel:)/', $href)) continue;

            $links[] = [
                'label' => mb_substr($text, 0, 80),
                'href' => $fullUrl,
            ];
        }

        return array_slice($links, 0, 30);
    }

    protected function extractSocialLinks(\DOMXPath $xpath): array
    {
        $social = [];
        $patterns = [
            'facebook' => '/facebook\.com/i',
            'twitter' => '/twitter\.com|x\.com/i',
            'instagram' => '/instagram\.com/i',
            'linkedin' => '/linkedin\.com/i',
            'youtube' => '/youtube\.com/i',
            'tiktok' => '/tiktok\.com/i',
            'pinterest' => '/pinterest\.com/i',
            'github' => '/github\.com/i',
        ];

        $nodes = $xpath->query('//a[@href]');
        foreach ($nodes as $node) {
            $href = $node->getAttribute('href');
            foreach ($patterns as $platform => $pattern) {
                if (preg_match($pattern, $href) && !isset($social[$platform])) {
                    $social[$platform] = $href;
                }
            }
        }

        return $social;
    }

    protected function extractContactInfo(\DOMXPath $xpath, string $html): array
    {
        $contact = [];

        // Email addresses
        preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $html, $emails);
        $emails = array_unique($emails[0] ?? []);
        // Filter out common non-contact emails
        $emails = array_filter($emails, fn($e) => !preg_match('/(sentry|webpack|example|test|noreply)/', $e));
        if (!empty($emails)) {
            $contact['emails'] = array_values(array_slice($emails, 0, 5));
        }

        // Phone numbers
        preg_match_all('/(?:tel:|phone:|call\s)?\+?[\d\s\-\(\)\.]{7,20}/', $html, $phones);
        $phones = array_unique(array_map('trim', $phones[0] ?? []));
        $phones = array_filter($phones, fn($p) => preg_match('/\d{7,}/', preg_replace('/\D/', '', $p)));
        if (!empty($phones)) {
            $contact['phones'] = array_values(array_slice($phones, 0, 5));
        }

        // Address (look for common address patterns)
        $nodes = $xpath->query('//*[contains(@class, "address") or contains(@class, "location") or contains(@itemtype, "PostalAddress")]');
        if ($nodes->length > 0) {
            $contact['address'] = trim($nodes->item(0)->textContent);
        }

        return $contact;
    }

    /**
     * Resolve a relative URL to an absolute URL.
     */
    protected function resolveUrl(string $href, string $baseUrl): ?string
    {
        if (empty($href) || preg_match('/^(javascript:|mailto:|tel:|data:|#)/', $href)) {
            return null;
        }

        if (preg_match('/^https?:\/\//', $href)) {
            return $href;
        }

        $parsed = parse_url($baseUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';

        if (str_starts_with($href, '//')) {
            return $scheme . ':' . $href;
        }

        if (str_starts_with($href, '/')) {
            return $scheme . '://' . $host . $href;
        }

        $basePath = $parsed['path'] ?? '/';
        $baseDir = rtrim(dirname($basePath), '/');
        return $scheme . '://' . $host . $baseDir . '/' . $href;
    }
}
