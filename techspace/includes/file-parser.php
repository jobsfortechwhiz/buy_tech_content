
<?php
// ─── File Content Parser ─────────────────────────────────────────────────────

/**
 * Parse a .txt file → return plain text
 */
function parseTxt(string $filepath): string|false {
    return file_get_contents($filepath);
}

/**
 * Parse a .docx file → extract plain text from its internal XML.
 * No external library needed — .docx is a ZIP containing word/document.xml.
 * Handles paragraphs, line breaks, tabs, and smart-quote normalisation.
 */
function parseDocx(string $filepath): string|false {
    if (!class_exists('ZipArchive')) {
        return "Error: ZipArchive extension is not enabled on this server.";
    }

    $zip = new ZipArchive();
    if ($zip->open($filepath) !== true) return false;

    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    if ($xml === false) return false;

    // ── Step 1: mark paragraph and line-break tags with a newline sentinel ──
    // w:p  = paragraph  →  new line
    // w:br = line break →  new line
    // w:tab = tab       →  space
    $xml = preg_replace('/<w:p\b[^>]*>/',  "\n", $xml);   // paragraph start
    $xml = preg_replace('/<\/w:p>/',        "\n", $xml);   // paragraph end (extra newline)
    $xml = preg_replace('/<w:br\b[^>]*\/>/', "\n", $xml);  // explicit line break
    $xml = preg_replace('/<w:tab\b[^>]*\/>/', " ",  $xml); // tab → space

    // ── Step 2: strip all remaining XML tags ──
    $text = strip_tags($xml);

    // ── Step 3: normalise whitespace ──
    // Smart quotes / special dashes → ASCII equivalents
    $text = str_replace(["\xE2\x80\x98", "\xE2\x80\x99"], "'",  $text); // '' → '
    $text = str_replace(["\xE2\x80\x9C", "\xE2\x80\x9D"], '"',  $text); // "" → "
    $text = str_replace(["\xE2\x80\x93", "\xE2\x80\x94"], '-',  $text); // – — → -
    $text = str_replace("\xC2\xA0", ' ', $text);                          // NBSP → space

    // Collapse multiple spaces on a single line; trim each line
    $lines = explode("\n", $text);
    $clean = [];
    foreach ($lines as $line) {
        $line = preg_replace('/[ \t]+/', ' ', $line);
        $line = trim($line);
        if ($line !== '') $clean[] = $line;
    }
    $text = implode("\n", $clean);

    return $text;
}

/**
 * Dispatch to the right parser based on file extension.
 * Returns ['content' => string, 'error' => string|null]
 */
function parseUploadedFile(string $tmpPath, string $originalName): array {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    switch ($ext) {
        case 'txt':
            $content = parseTxt($tmpPath);
            return $content !== false
                ? ['content' => $content, 'error' => null]
                : ['content' => '',        'error' => 'Could not read text file.'];

        case 'docx':
            $content = parseDocx($tmpPath);
            return $content !== false
                ? ['content' => $content, 'error' => null]
                : ['content' => '',        'error' => 'Could not parse .docx — ensure it is a valid Word document and the server has ZipArchive enabled.'];

        default:
            return ['content' => '', 'error' => "Unsupported file type: .$ext"];
    }
}

/**
 * Extract numbered entries from plain text.
 *
 * The uploaded file MUST contain items prefixed with a number followed by a
 * dot at the start of a line, e.g.:
 *
 *   1. First item text (may span
 *      multiple lines / paragraphs)
 *   2. Second item text
 *   ...
 *   10. Multi-digit numbers are supported
 *   11. ...
 *
 * The content is split using "1.", "2.", "3." … as delimiters. Any text
 * BEFORE the first "1." is discarded (headings, intro, etc.). Numbering is
 * treated as sequential — the parser only accepts markers that continue the
 * sequence (1, 2, 3, …). Stray digits inside an entry (e.g. "in 2020.") are
 * therefore ignored.
 *
 * Returns array of ['number' => int, 'content' => string] in order 1..n.
 */
function extractNumberedEntries(string $content): array {
    if ($content === '') return [];

    // Normalise line endings so ^ / $ work predictably in multiline mode.
    $content = preg_replace('/\r\n?/', "\n", $content);

    // Find every candidate marker: "N." at the start of a line
    // (optionally indented) followed by whitespace.
    //   ^\s*      → optional leading whitespace on the line
    //   (\d{1,4}) → 1-to-4 digit number (supports 1 … 9999)
    //   \.        → literal dot
    //   \s+       → at least one whitespace char before the content
    if (!preg_match_all(
        '/^[ \t]*(\d{1,4})\.[ \t]+/m',
        $content,
        $matches,
        PREG_OFFSET_CAPTURE
    )) {
        return [];
    }

    // Keep only sequential markers starting at 1 → 2 → 3 → …
    // This ignores duplicate numbers and stray "3." that may appear
    // inside an entry's body.
    $selected = [];
    $expected = 1;
    foreach ($matches[0] as $i => $full) {
        $num = (int) $matches[1][$i][0];
        if ($num === $expected) {
            $selected[] = [
                'offset' => $full[1],
                'length' => strlen($full[0]),
                'number' => $num,
            ];
            $expected++;
        }
    }

    if (!$selected) return [];

    // Slice the content between consecutive markers → one entry per row.
    $result = [];
    $total  = count($selected);
    for ($i = 0; $i < $total; $i++) {
        $start = $selected[$i]['offset'] + $selected[$i]['length'];
        $end   = ($i + 1 < $total)
            ? $selected[$i + 1]['offset']
            : strlen($content);

        $text = substr($content, $start, $end - $start);

        // Collapse internal whitespace (multi-line entries become one line)
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if ($text !== '') {
            $result[] = [
                'number'  => $selected[$i]['number'],
                'content' => $text,
            ];
        }
    }

    return $result;
}
