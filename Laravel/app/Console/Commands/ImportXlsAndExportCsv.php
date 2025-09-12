<?php

namespace App\Console\Commands;

use SplFileObject;
use App\Models\contract;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportXlsAndExportCsv extends Command
{
    protected $signature = 'import:xls-to-csv';
    protected $description = 'Legge un file XLS, cerca nel DB e crea un CSV con gli ID trovati.';

    public function handle()
    {
        ini_set('memory_limit', '1024M');

        $pathXls = storage_path('app/input/contract_ripulito.xls');
        $outputCsv = storage_path('app/output/contract_ripulito_match.csv');
        $colToRead = 1;

        $reader = IOFactory::createReader('Xls');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($pathXls);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $totalRows = count($rows);
        $this->info("Trovate {$totalRows} righe nel file XLS. Inizio controllo...");

        $csv = new SplFileObject($outputCsv, 'w');
        $progressBar = $this->output->createProgressBar($totalRows);
        $progressBar->start();

        foreach ($rows as $index => $row) {
            // Prima riga: intestazione
            if ($index === 0) {
                $row[] = 'id';
                $csv->fputcsv($row);
                $progressBar->advance();
                continue;
            }

            $valore = $row[$colToRead] ?? null;

            if ($valore) {
                $contratto = contract::where('codice_contratto', $valore)->first();
                $id = $contratto ? $contratto->id : '';
            } else {
                $id = '';
            }

            $row[] = $id;
            $csv->fputcsv($row);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->info("\nFatto. CSV creato in: " . $outputCsv);
    }
}
