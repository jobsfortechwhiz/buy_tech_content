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
 * Recognised formats on a line by itself:
 *   1. text       1) text     (1) text
 *   1: text       1 - text    1  text   (number followed by space only)
 *
 * Multi-line entries are also handled — if a line does NOT start with a
 * number it is appended to the previous entry (continuation line).
 *
 * Returns array of ['number' => int, 'content' => string] sorted by number.
 */
function extractNumberedEntries(string $content): array {
    $entries = [];
    $lines   = preg_split('/\r?\n/', $content);

    $currentNum  = null;
    $currentText = '';

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;

        // Does this line open a new numbered entry?
        // Pattern covers:  1.  1)  1:  (1)  1 -  1  <text>
        if (preg_match(
            '/^(?:\()?(\d{1,3})(?:\))?(?:[.):\-]|\s)\s*(.*)$/u',
            $line,
            $m
        )) {
            // Save previous entry
            if ($currentNum !== null && $currentText !== '') {
                _saveEntry($entries, $currentNum, $currentText);
            }
            $currentNum  = (int) $m[1];
            $currentText = trim($m[2]);
        } else {
            // Continuation line — append to current entry
            if ($currentNum !== null) {
                $currentText .= ' ' . $line;
            }
            // (Lines before any numbered entry are ignored)
        }
    }

    // Flush last entry
    if ($currentNum !== null && $currentText !== '') {
        _saveEntry($entries, $currentNum, $currentText);
    }

    ksort($entries);

    $result = [];
    foreach ($entries as $num => $text) {
        $result[] = ['number' => $num, 'content' => $text];
    }
    return $result;
}

/** Internal: add/merge an entry keeping the longer version. */
function _saveEntry(array &$entries, int $num, string $text): void {
    if ($num <= 0 || $text === '') return;
    if (!isset($entries[$num]) || strlen($text) > strlen($entries[$num])) {
        $entries[$num] = $text;
    }
}
