<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Extracts text content from uploaded files for AI context.
 *
 * Supported formats:
 * - Plain text (.txt, .md, .csv, .html, .rtf)
 * - PDF (.pdf) — requires pdftotext CLI or falls back to basic extraction
 * - Word (.docx) — uses ZIP + XML parsing (no external dependencies)
 * - Google Docs (public URL) — fetches export as text
 */
class FileExtractor
{
    /**
     * Maximum characters to extract from a single file.
     * Prevents extremely large files from overwhelming the AI context.
     */
    protected int $maxChars = 50000;

    /**
     * Supported MIME types and their extraction methods.
     */
    protected array $supportedTypes = [
        'text/plain' => 'extractText',
        'text/csv' => 'extractText',
        'text/html' => 'extractHtml',
        'text/markdown' => 'extractText',
        'text/rtf' => 'extractRtf',
        'application/rtf' => 'extractRtf',
        'application/pdf' => 'extractPdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'extractDocx',
        'application/msword' => 'extractDoc',
        'application/zip' => 'extractZip',
        'application/x-zip-compressed' => 'extractZip',
    ];

    /**
     * Supported file extensions (fallback when MIME detection fails).
     */
    protected array $supportedExtensions = [
        'txt' => 'extractText',
        'md' => 'extractText',
        'csv' => 'extractText',
        'html' => 'extractHtml',
        'htm' => 'extractHtml',
        'rtf' => 'extractRtf',
        'pdf' => 'extractPdf',
        'docx' => 'extractDocx',
        'doc' => 'extractDoc',
        'zip' => 'extractZip',
    ];

    /**
     * Extract text content from an uploaded file.
     *
     * @return array{success: bool, content?: string, filename: string, error?: string, chars: int}
     */
    public function extract(UploadedFile $file): array
    {
        $filename = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        // Determine extraction method
        $method = $this->supportedTypes[$mimeType] ?? $this->supportedExtensions[$extension] ?? null;

        if (!$method) {
            return [
                'success' => false,
                'filename' => $filename,
                'error' => "Unsupported file type: {$mimeType} (.{$extension})",
                'chars' => 0,
            ];
        }

        try {
            $content = $this->$method($file);

            if (empty(trim($content))) {
                return [
                    'success' => false,
                    'filename' => $filename,
                    'error' => 'No text content could be extracted from this file.',
                    'chars' => 0,
                ];
            }

            // Truncate if too long
            $content = $this->truncate($content);

            return [
                'success' => true,
                'filename' => $filename,
                'content' => $content,
                'chars' => strlen($content),
            ];

        } catch (\Exception $e) {
            Log::error('File extraction failed', [
                'filename' => $filename,
                'mime' => $mimeType,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'filename' => $filename,
                'error' => 'Failed to extract text: ' . $e->getMessage(),
                'chars' => 0,
            ];
        }
    }

    /**
     * Check if a file type is supported.
     */
    public function isSupported(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        return isset($this->supportedTypes[$mimeType]) || isset($this->supportedExtensions[$extension]);
    }

    /**
     * Get list of supported file extensions for the UI.
     */
    public static function getSupportedExtensions(): array
    {
        return ['txt', 'md', 'csv', 'html', 'htm', 'rtf', 'pdf', 'docx', 'doc', 'zip'];
    }

    /**
     * Extract from plain text files (TXT, MD, CSV).
     */
    protected function extractText(UploadedFile $file): string
    {
        return file_get_contents($file->getRealPath());
    }

    /**
     * Extract from HTML files — save raw file for CMS import, return metadata only.
     *
     * HTML files are special: we save the FULL raw content to disk so the
     * ImportHtmlTool can access it directly. We return ONLY the metadata
     * (storage path + instructions) — NOT the raw HTML — to avoid exceeding
     * the chat message size limit (10,000 chars).
     */
    protected function extractHtml(UploadedFile $file): string
    {
        $html = file_get_contents($file->getRealPath());
        $originalName = $file->getClientOriginalName();

        // Save the raw HTML file to storage for the ImportHtmlTool to use
        $filename = 'html-imports/' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);

        // Ensure the directory exists
        $dir = storage_path('app/html-imports');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Write directly to filesystem (more reliable than Storage facade on shared hosting)
        $storagePath = storage_path('app/' . $filename);
        file_put_contents($storagePath, $html);

        // Extract a brief summary for the AI context (NOT the full HTML)
        $title = '';
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $titleMatch)) {
            $title = trim(strip_tags($titleMatch[1]));
        }
        $bodyText = strip_tags(preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html));
        $bodyText = preg_replace('/\s+/', ' ', $bodyText);
        $summary = substr(trim($bodyText), 0, 200);

        // Return ONLY metadata — keep it well under the 10K message limit
        // NOTE: The storage_path MUST be used EXACTLY as shown below.
        $result = "[HTML FILE READY FOR IMPORT]\n";
        $result .= "\n";
        $result .= "EXACT storage_path (use this VERBATIM): {$storagePath}\n";
        $result .= "\n";
        $result .= "Original filename: {$originalName}\n";
        $result .= "File size: " . strlen($html) . " bytes\n";
        if ($title) {
            $result .= "Page title: {$title}\n";
        }
        $result .= "Content preview: {$summary}...\n";
        $result .= "\n";
        $result .= "ACTION REQUIRED: Call import_html_page with the EXACT storage_path shown above.\n";
        $result .= "CORRECT call: import_html_page(storage_path: \"{$storagePath}\", title: \"...\", slug: \"...\")\n";
        $result .= "WARNING: Do NOT change or shorten the storage_path. Use the full absolute path exactly as provided.\n";
        $result .= "The tool will automatically extract all CSS, preserve the HTML structure, and create editable fields.";

        return $result;
    }

    /**
     * Extract from RTF files — basic text extraction.
     */
    protected function extractRtf(UploadedFile $file): string
    {
        $rtf = file_get_contents($file->getRealPath());

        // Strip RTF control words and groups
        $text = preg_replace('/\{\\\\[^{}]*\}/', '', $rtf);
        $text = preg_replace('/\\\\[a-z]+\d*\s?/i', '', $text);
        $text = preg_replace('/[{}]/', '', $text);

        // Clean up
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Extract from PDF files.
     * Tries pdftotext CLI first, then falls back to basic extraction.
     */
    protected function extractPdf(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        // Try pdftotext (poppler-utils) — most reliable
        if ($this->commandExists('pdftotext')) {
            $output = [];
            $returnCode = 0;
            exec("pdftotext -layout " . escapeshellarg($path) . " - 2>/dev/null", $output, $returnCode);

            if ($returnCode === 0 && !empty($output)) {
                return implode("\n", $output);
            }
        }

        // Fallback: basic PDF text extraction using PHP
        return $this->extractPdfBasic($path);
    }

    /**
     * Basic PDF text extraction without external tools.
     * Handles simple PDFs with uncompressed text streams.
     */
    protected function extractPdfBasic(string $path): string
    {
        $content = file_get_contents($path);
        $text = '';

        // Try to find text in stream objects
        if (preg_match_all('/stream\s*\n(.*?)\nendstream/s', $content, $matches)) {
            foreach ($matches[1] as $stream) {
                // Try to decompress if it's a FlateDecode stream
                $decompressed = @gzuncompress($stream);
                if ($decompressed !== false) {
                    $stream = $decompressed;
                }

                // Extract text from content stream operators (Tj, TJ, ')
                if (preg_match_all('/\((.*?)\)\s*Tj/s', $stream, $textMatches)) {
                    $text .= implode(' ', $textMatches[1]) . "\n";
                }
                if (preg_match_all('/\[(.*?)\]\s*TJ/s', $stream, $tjMatches)) {
                    foreach ($tjMatches[1] as $tjContent) {
                        if (preg_match_all('/\((.*?)\)/', $tjContent, $innerMatches)) {
                            $text .= implode('', $innerMatches[1]) . "\n";
                        }
                    }
                }
            }
        }

        // Clean up PDF escape sequences
        $text = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $text);

        return trim($text) ?: 'Unable to extract text from this PDF. The file may contain scanned images or use an unsupported encoding. Try converting it to a text file first.';
    }

    /**
     * Extract from DOCX files using ZIP + XML parsing.
     * No external dependencies needed — uses PHP's built-in ZipArchive.
     */
    protected function extractDocx(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('ZipArchive extension is required for DOCX extraction.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Failed to open DOCX file.');
        }

        // Read the main document content
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new \RuntimeException('Invalid DOCX file: missing document.xml');
        }

        // Parse XML and extract text
        $dom = new \DOMDocument();
        @$dom->loadXML($xml);

        $text = '';
        $paragraphs = $dom->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'p');

        foreach ($paragraphs as $paragraph) {
            $runs = $paragraph->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 't');
            $paragraphText = '';
            foreach ($runs as $run) {
                $paragraphText .= $run->textContent;
            }
            if (!empty(trim($paragraphText))) {
                $text .= $paragraphText . "\n";
            }
        }

        return trim($text);
    }

    /**
     * Extract from legacy DOC files — very basic extraction.
     */
    protected function extractDoc(UploadedFile $file): string
    {
        $content = file_get_contents($file->getRealPath());

        // Try to extract readable text from binary DOC format
        // This is a very basic approach — works for simple documents
        $text = '';

        // Look for text between common DOC markers
        if (preg_match_all('/[\x20-\x7E]{4,}/', $content, $matches)) {
            $text = implode(' ', $matches[0]);
        }

        // Clean up
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text) ?: 'Unable to extract text from this .doc file. Please save it as .docx or .txt format and try again.';
    }

    /**
     * Extract from ZIP files — save to disk for ImportZipTool, return metadata only.
     *
     * ZIP files are handled like HTML files: the raw archive is saved to disk
     * and only metadata is returned to the AI context. The ImportZipTool
     * reads the ZIP directly from the filesystem.
     */
    protected function extractZip(UploadedFile $file): string
    {
        $originalName = $file->getClientOriginalName();

        // Save the ZIP file to storage for the ImportZipTool to use
        $filename = 'zip-imports/' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);

        // Ensure the directory exists
        $dir = storage_path('app/zip-imports');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $storagePath = storage_path('app/' . $filename);
        copy($file->getRealPath(), $storagePath);

        // Scan the ZIP contents to provide a summary
        $htmlCount = 0;
        $imageCount = 0;
        $cssCount = 0;
        $otherCount = 0;
        $htmlFiles = [];
        $totalSize = filesize($storagePath);

        $zip = new \ZipArchive();
        if ($zip->open($storagePath) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                // Skip directories and macOS metadata
                if (str_ends_with($entry, '/') || str_contains($entry, '__MACOSX')) {
                    continue;
                }
                $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
                if (in_array($ext, ['html', 'htm'])) {
                    $htmlCount++;
                    $htmlFiles[] = basename($entry);
                } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico', 'avif'])) {
                    $imageCount++;
                } elseif ($ext === 'css') {
                    $cssCount++;
                } else {
                    $otherCount++;
                }
            }
            $zip->close();
        }

        // Return metadata for the AI
        // NOTE: The storage_path MUST be used EXACTLY as shown below — do NOT modify or shorten it.
        $result = "[ZIP ARCHIVE READY FOR IMPORT]\n";
        $result .= "\n";
        $result .= "EXACT storage_path (use this VERBATIM): {$storagePath}\n";
        $result .= "\n";
        $result .= "Original filename: {$originalName}\n";
        $result .= "Archive size: " . number_format($totalSize / 1024, 1) . " KB\n";
        $result .= "Contents: {$htmlCount} HTML file(s), {$imageCount} image(s), {$cssCount} CSS file(s)\n";
        if (!empty($htmlFiles)) {
            $result .= "HTML files: " . implode(', ', array_slice($htmlFiles, 0, 20)) . "\n";
        }
        $result .= "\n";
        $result .= "ACTION REQUIRED: Call import_zip_site with the EXACT storage_path shown above.\n";
        $result .= "CORRECT call: import_zip_site(storage_path: \"{$storagePath}\")\n";
        $result .= "WARNING: Do NOT change or shorten the storage_path. Use the full absolute path exactly as provided.\n";
        $result .= "The tool will automatically import images to the media library, process each HTML page, and create CMS pages.";

        return $result;
    }

    /**
     * Truncate content to the maximum character limit.
     */
    protected function truncate(string $content): string
    {
        if (strlen($content) <= $this->maxChars) {
            return $content;
        }

        // Truncate at a word boundary
        $truncated = substr($content, 0, $this->maxChars);
        $lastSpace = strrpos($truncated, ' ');
        if ($lastSpace !== false && $lastSpace > $this->maxChars * 0.9) {
            $truncated = substr($truncated, 0, $lastSpace);
        }

        return $truncated . "\n\n[Content truncated — file exceeds " . number_format($this->maxChars) . " character limit]";
    }

    /**
     * Check if a CLI command exists.
     */
    protected function commandExists(string $command): bool
    {
        $return = shell_exec("which " . escapeshellarg($command) . " 2>/dev/null");
        return !empty(trim($return ?? ''));
    }

    /**
     * Set the maximum characters to extract.
     */
    public function setMaxChars(int $maxChars): self
    {
        $this->maxChars = $maxChars;
        return $this;
    }

    /**
     * Extract text from a Google Docs URL.
     * Works with publicly shared documents.
     *
     * @return array{success: bool, content?: string, error?: string}
     */
    public static function extractFromGoogleDoc(string $url): array
    {
        // Extract the document ID from various Google Docs URL formats
        $docId = null;
        if (preg_match('/\/document\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $docId = $matches[1];
        } elseif (preg_match('/\/open\?id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $docId = $matches[1];
        }

        if (!$docId) {
            return [
                'success' => false,
                'error' => 'Invalid Google Docs URL. Please share a valid Google Docs link.',
            ];
        }

        // Use the export URL to get plain text
        $exportUrl = "https://docs.google.com/document/d/{$docId}/export?format=txt";

        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 15,
                    'follow_location' => true,
                    'max_redirects' => 5,
                    'header' => "User-Agent: Mozilla/5.0\r\n",
                ],
            ]);

            $content = @file_get_contents($exportUrl, false, $context);

            if ($content === false) {
                return [
                    'success' => false,
                    'error' => 'Could not access the Google Doc. Make sure it is shared publicly (Anyone with the link can view).',
                ];
            }

            $content = trim($content);
            if (empty($content)) {
                return [
                    'success' => false,
                    'error' => 'The Google Doc appears to be empty.',
                ];
            }

            return [
                'success' => true,
                'content' => $content,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch Google Doc: ' . $e->getMessage(),
            ];
        }
    }
}
