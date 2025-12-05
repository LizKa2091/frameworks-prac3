<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LegacyController extends Controller
{
    public function csvView()
    {
        $csvDir = env('CSV_OUT_DIR', '/data/csv');
        $files = [];
        
        if (is_dir($csvDir)) {
            $files = array_filter(scandir($csvDir), function($file) use ($csvDir) {
                return pathinfo($file, PATHINFO_EXTENSION) === 'csv';
            });
            rsort($files); // новейшие первыми
        }
        
        return view('legacy.csv', ['files' => $files, 'csvDir' => $csvDir]);
    }
    
    public function csvContent(Request $request)
    {
        $filename = $request->query('file');
        $csvDir = env('CSV_OUT_DIR', '/data/csv');
        $filepath = $csvDir . '/' . basename($filename);
        
        if (!file_exists($filepath) || pathinfo($filepath, PATHINFO_EXTENSION) !== 'csv') {
            return response()->json(['error' => 'File not found'], 404);
        }
        
        $rows = [];
        if (($handle = fopen($filepath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== false) {
                $row = [];
                foreach ($headers as $index => $header) {
                    $row[$header] = $data[$index] ?? '';
                }
                $rows[] = $row;
            }
            fclose($handle);
        }
        
        return response()->json([
            'headers' => $headers ?? [],
            'rows' => $rows
        ]);
    }
}