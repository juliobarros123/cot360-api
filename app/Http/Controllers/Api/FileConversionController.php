<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class FileConversionController extends Controller
{
    //
// public function convertPdfToExcel(Request $request)
// {
//     $request->validate([
//         'pdf_file' => 'required|file|mimes:pdf|max:10240',
//         'output_extension' => 'sometimes|in:xlsx,xls',
//     ]);

//     $pdfFile = $request->file('pdf_file');
//     $outputExtension = $request->input('output_extension', 'xlsx');
//     $outputFilename = 'converted_' . time() . '.' . $outputExtension;
    
//     // Diretório público para armazenar os arquivos convertidos
//     $publicPath = 'converted_files/' . date('Y/m/d');
//     $storagePath = storage_path('app/public/' . $publicPath);
    
//     if (!file_exists($storagePath)) {
//         mkdir($storagePath, 0755, true);
//     }

//     try {
//         // 1. Salvar o PDF temporariamente
//         $pdfPath = $storagePath . '/temp_' . uniqid() . '.pdf';
//         $pdfFile->move($storagePath, basename($pdfPath));

//         // 2. Caminho do arquivo Excel de saída
//         $outputPath = $storagePath . '/' . $outputFilename;

//         // 3. Executar conversão
//         $pythonScript = base_path('scripts/pdf_to_excel.py');
//         $process = new Process([
//             'python3',
//             $pythonScript,
//             $pdfPath,
//             $outputPath
//         ]);
        
//         $process->setTimeout(300);
//         $process->run();

//         if (!$process->isSuccessful()) {
//             throw new ProcessFailedException($process);
//         }

//         if (!file_exists($outputPath)) {
//             throw new \Exception('Arquivo de saída não foi gerado');
//         }

//         // 4. Criar link simbólico se não existir
//         if (!file_exists(public_path('storage'))) {
//             \Artisan::call('storage:link');
//         }

//         // 5. Retornar URL pública do arquivo
//         $publicUrl = asset('storage/' . $publicPath . '/' . $outputFilename);

//         return response()->json([
//             'success' => true,
//             'download_url' => $publicUrl,
//             'filename' => $outputFilename,
//             'message' => 'Conversão concluída com sucesso'
//         ]);

//     } catch (\Exception $e) {
//         // Limpar arquivos temporários em caso de erro
//         if (isset($storagePath) && file_exists($storagePath)) {
//             array_map('unlink', glob("$storagePath/*"));
//         }

//         return response()->json([
//             'success' => false,
//             'error' => 'Falha na conversão',
//             'message' => $e->getMessage()
//         ], 500);
//     }
// }


public function convertPdfToWord(Request $request)
{
    return $this->convertPdf($request, 'docx');
}

public function convertPdfToExcel(Request $request)
{
    return $this->convertPdf($request, 'xlsx');
}

private function convertPdf(Request $request, string $format)
{
    $request->validate([
        'pdf_file' => 'required|file|mimes:pdf|max:10240',
    ]);

    $pdfFile = $request->file('pdf_file');
    $outputFilename = 'converted_' . time() . '.' . $format;
    $publicPath = 'converted_files/' . date('Y/m/d');
    $storagePath = storage_path('app/public/' . $publicPath);

    if (!file_exists($storagePath)) {
        mkdir($storagePath, 0755, true);
    }

    try {
        $pdfPath = $storagePath . '/temp_' . uniqid() . '.pdf';
        $pdfFile->move($storagePath, basename($pdfPath));
        $outputPath = $storagePath . '/' . $outputFilename;

        $pythonScript = $format === 'docx' 
            ? base_path('scripts/pdf_to_docx.py')
            : base_path('scripts/pdf_to_excel.py');

        $process = new Process(['python3', $pythonScript, $pdfPath, $outputPath]);
      $process->setTimeout(600); 
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        if (!file_exists($outputPath)) {
            throw new \Exception('Arquivo de saída não foi gerado');
        }

        if (!file_exists(public_path('storage'))) {
            \Artisan::call('storage:link');
        }

        return response()->json([
            'success' => true,
            'download_url' => asset('storage/' . $publicPath . '/' . $outputFilename),
            'filename' => $outputFilename,
            'message' => 'Conversão concluída com sucesso'
        ]);

    } catch (\Exception $e) {
        if (isset($storagePath) && file_exists($storagePath)) {
            array_map('unlink', glob("$storagePath/*"));
        }

        return response()->json([
            'success' => false,
            'error' => 'Falha na conversão',
            'message' => $e->getMessage()
        ], 500);
    }
}
}
