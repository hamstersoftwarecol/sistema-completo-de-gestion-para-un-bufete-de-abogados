<?php

namespace App\Services;

/**
 * Genera PDFs de texto muy sencillos (se usa para documentos de demostración).
 */
class SimplePdf
{
    public static function make(string $title, array $paragraphs): string
    {
        $enc = fn (string $s) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'],
            mb_convert_encoding($s, 'Windows-1252', 'UTF-8'));

        $stream = 'BT /F1 16 Tf 72 740 Td ('.$enc($title).") Tj ET\n";
        $stream .= "0.6 G 72 728 m 540 728 l S 0 G\n";

        $y = 705;
        foreach ($paragraphs as $paragraph) {
            foreach (explode("\n", wordwrap($paragraph, 92, "\n", true)) as $line) {
                if ($y < 72) {
                    break 2;
                }
                $stream .= "BT /F2 11 Tf 72 {$y} Td (".$enc($line).") Tj ET\n";
                $y -= 15;
            }
            $y -= 8;
        }

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n".$stream.'endstream',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$obj}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= 'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        return $pdf;
    }
}
