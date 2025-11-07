<?php
/**
 * SimpleExcelReader - Leitor simples de arquivos Excel (.xlsx)
 * Usa ZIP e XML nativos do PHP, sem dependências externas
 */

class SimpleExcelReader {
    private $filePath;
    private $sharedStrings = [];
    private $sheets = [];

    public function __construct($filePath) {
        $this->filePath = $filePath;
    }

    /**
     * Lê o arquivo Excel e retorna os dados como array
     */
    public function read() {
        if (!file_exists($this->filePath)) {
            throw new Exception("Arquivo não encontrado: {$this->filePath}");
        }

        // Verifica se é um arquivo ZIP válido (arquivos .xlsx são ZIP)
        $zip = new ZipArchive();
        if ($zip->open($this->filePath) !== true) {
            throw new Exception("Não foi possível abrir o arquivo Excel. Verifique se o arquivo está corrompido.");
        }

        // Lê strings compartilhadas (se existir)
        if ($zip->locateName('xl/sharedStrings.xml') !== false) {
            $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
            $this->parseSharedStrings($sharedStringsXml);
        }

        // Lê a primeira planilha
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();
            throw new Exception("Não foi possível ler a planilha. Verifique se o arquivo Excel é válido.");
        }

        $zip->close();

        // Parseia a planilha
        return $this->parseSheet($sheetXml);
    }

    /**
     * Parseia as strings compartilhadas
     */
    private function parseSharedStrings($xml) {
        $xmlObj = simplexml_load_string($xml);
        if ($xmlObj === false) {
            return;
        }

        $xmlObj->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $strings = $xmlObj->xpath('//x:si');

        foreach ($strings as $string) {
            $value = '';
            // Concatena todos os textos (pode ter formatação)
            foreach ($string->xpath('.//x:t') as $t) {
                $value .= (string)$t;
            }
            $this->sharedStrings[] = $value;
        }
    }

    /**
     * Parseia a planilha e retorna os dados como array
     */
    private function parseSheet($xml) {
        $xmlObj = simplexml_load_string($xml);
        if ($xmlObj === false) {
            throw new Exception("Erro ao parsear XML da planilha");
        }

        $xmlObj->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = $xmlObj->xpath('//x:row');

        $data = [];
        foreach ($rows as $row) {
            $rowData = [];
            $cells = $row->xpath('.//x:c');

            foreach ($cells as $cell) {
                $value = '';
                $type = (string)$cell['t'];

                if ($type === 's') {
                    // String compartilhada
                    $index = (int)$cell->v;
                    $value = $this->sharedStrings[$index] ?? '';
                } elseif ($type === 'inlineStr') {
                    // String inline
                    $is = $cell->xpath('.//x:is/x:t');
                    $value = (string)($is[0] ?? '');
                } else {
                    // Número ou outro tipo
                    $value = (string)$cell->v;
                }

                $rowData[] = $value;
            }

            // Só adiciona linhas não vazias
            if (!empty(array_filter($rowData))) {
                $data[] = $rowData;
            }
        }

        return $data;
    }

    /**
     * Lê arquivo Excel e retorna como array (método estático)
     */
    public static function readFile($filePath) {
        $reader = new self($filePath);
        return $reader->read();
    }

    /**
     * Verifica se um arquivo é Excel (.xlsx)
     */
    public static function isExcelFile($filePath) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return in_array($extension, ['xlsx', 'xls']);
    }
}
