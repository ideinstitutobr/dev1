<?php
/**
 * Gera Template Excel para Importação de Colaboradores
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega classe SimpleExcelWriter
class SimpleExcelWriter {
    public static function create($filename, $data) {
        // Cria estrutura de arquivo Excel (.xlsx)
        $zip = new ZipArchive();
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');

        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Não foi possível criar arquivo Excel");
        }

        // [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // xl/_rels/workbook.xml.rels
        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);

        // xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Colaboradores" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // xl/worksheets/sheet1.xml
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <sheetData>';

        $rowNum = 1;
        foreach ($data as $row) {
            $sheet .= '<row r="' . $rowNum . '">';
            $colNum = 0;
            foreach ($row as $cell) {
                $colLetter = chr(65 + $colNum); // A, B, C...
                $cellRef = $colLetter . $rowNum;
                $sheet .= '<c r="' . $cellRef . '" t="inlineStr"><is><t>' . htmlspecialchars($cell) . '</t></is></c>';
                $colNum++;
            }
            $sheet .= '</row>';
            $rowNum++;
        }

        $sheet .= '</sheetData>
</worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);

        $zip->close();

        // Envia arquivo para download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tempFile));
        readfile($tempFile);
        unlink($tempFile);
        exit;
    }
}

// Dados do template
$data = [
    ['Nome', 'CPF', 'E-mail'], // Cabeçalho
    ['João da Silva', '123.456.789-00', 'joao@empresa.com'],
    ['Maria Santos', '987.654.321-00', 'maria@empresa.com'],
    ['Pedro Oliveira', '111.222.333-44', 'pedro@empresa.com']
];

SimpleExcelWriter::create('template_colaboradores.xlsx', $data);
