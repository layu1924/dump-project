<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Project;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class QaReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reports = Report::with(['project', 'findings'])->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $reports
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validasi Input (Input 'tester' dihapus karena diambil otomatis)
        $validated = $request->validate([
            'project_name' => 'required|string',
            'findings' => 'required|array',
            'findings.*.description' => 'required|string',
            'findings.*.status' => 'required|string',
            'findings.*.severity' => 'nullable|string',
            'findings.*.image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $project = Project::firstOrCreate([
            'name' => $validated['project_name']
        ]);

        // 3. Simpan Data Report (Gunakan Auth::user()->name)
        $report = Report::create([
            'project_id' => $project->id,
            'tester' => auth()->user()->name, // <-- Nama diambil dari akun yang login
        ]);

        // 4. Proses dan Simpan Temuan berserta Gambar
        $findingsData = [];

        $requestFindings = $request->findings ?? [];

        foreach ($requestFindings as $index => $findingsData) {
            $imagePath = null;

            if (isset($findingsData['image']) && $findingsData['image'] instanceof \Illuminate\Http\UploadedFile){
                $imagePath = $findingsData['image']->store('findings', 'public');
            }
        }

        $findingsData = [];
        foreach ($validated['findings'] as $finding){
            $findingsData[] = [
                'description' => $finding['description'],
                'status' => $finding['status'],
                'severity' => $finding['severity'] ?? null,
                'image_path' => $imagePath,
            ];
        }
        $report->findings()->createMany($findingsData);

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan berhasil disimpan ke database',
            'data' => $report->load(['project', 'findings'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $report = Report::with(['project', 'findings'])->find($id);

        if (!$report) {
            return response()->json(['status' => 'error', 'message' => 'Laporan tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $report
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage (Cascade Delete).
     */
    public function destroy(string $id)
    {
        // 1. Cari laporan beserta data temuan-temuannya
        $report = Report::with('findings')->find($id);

        if (!$report) {
            return response()->json(['status' => 'error', 'message' => 'Laporan tidak ditemukan'], 404);
        }

        // 2. Hapus file fisik gambar (screenshot) dari folder storage
        foreach ($report->findings as $finding) {
            if ($finding->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($finding->image_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($finding->image_path);
            }
        }

        // 3. Hapus baris temuan (findings) dari database
        $report->findings()->delete();

        // 4. Hapus laporan utama (reports) dari database
        $report->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan dan bukti fisik berhasil dibersihkan.'
        ], 200);
    }

    /**
     * Export the specified resource to a .docx file.
     */
    public function export($id)
    {
        // 1. Cari laporan beserta data project dan findings-nya
        $report = Report::with(['project', 'findings'])->find($id);

        if (!$report) {
            return response()->json(['status' => 'error', 'message' => 'Laporan tidak ditemukan'], 404);
        }

        // 2. Inisialisasi PhpWord
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        
        // Atur gaya font bawaan
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection();

        // 3. Tambahkan Header Laporan (Judul di tengah)
        $section->addText(
            'QA Automated Report',
            ['bold' => true, 'size' => 16],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 400]
        );

        // Buat penggaris tab khusus untuk identitas laporan (posisi 3000 twips)
        $phpWord->addParagraphStyle('HeaderTab', [
            'tabs' => [
                new \PhpOffice\PhpWord\Style\Tab('left', 3000)
            ]
        ]);

        // Gaya font seragam untuk identitas laporan (Bold, Size 14)
        $headerFontStyle = ['bold' => true, 'size' => 14];

        // 4. Informasi Utama (Identitas Laporan)
        $textRunProject = $section->addTextRun('HeaderTab');
        $textRunProject->addText('Project Name', $headerFontStyle);
        $textRunProject->addText("\t: " . $report->project->name, $headerFontStyle);

        $textRunTester = $section->addTextRun('HeaderTab');
        $textRunTester->addText('Tester', $headerFontStyle);
        $textRunTester->addText("\t: " . $report->tester, $headerFontStyle);

        $textRunDate = $section->addTextRun('HeaderTab');
        $textRunDate->addText('Tanggal', $headerFontStyle);
        $textRunDate->addText("\t: " . $report->created_at->format('Y-m-d H:i'), $headerFontStyle);
        
        // Berikan jarak (enter) sebelum masuk ke daftar temuan
        $section->addTextBreak(1);

        // 5. Looping Daftar Temuan (Format Vertikal / List Murni dengan Tab Stop)
        
        // Membuat gaya paragraf khusus dengan penggaris Tab (Tab Stop) di posisi 2500 twips (sekitar 4.4 cm)
        $phpWord->addParagraphStyle('AlignedColon', [
            'tabs' => [
                new \PhpOffice\PhpWord\Style\Tab('left', 2500)
            ]
        ]);

        foreach ($report->findings as $finding) {
            
            // Baris Deskripsi
            $textRunDesc = $section->addTextRun('AlignedColon');
            $textRunDesc->addText('Deskripsi Temuan', ['bold' => true]);
            $textRunDesc->addText("\t: " . $finding->description); // Hanya butuh 1 \t

            // Baris Status
            $textRunStatus = $section->addTextRun('AlignedColon');
            $textRunStatus->addText('Status', ['bold' => true]);
            $textRunStatus->addText("\t: " . $finding->status); // Hanya butuh 1 \t

            // Baris Severity
            $textRunSeverity = $section->addTextRun('AlignedColon');
            $textRunSeverity->addText('Severity', ['bold' => true]);
            $textRunSeverity->addText("\t: " . ($finding->severity ?? '-')); // Hanya butuh 1 \t

            // Baris Bukti Gambar
            $textRunImage = $section->addTextRun('AlignedColon');
            $textRunImage->addText('Bukti Gambar', ['bold' => true]);
            $textRunImage->addText("\t: "); // Hanya butuh 1 \t

            // Sisipkan Gambar
            if ($finding->image_path) {
                $fullImagePath = storage_path('app/public/' . $finding->image_path);
                
                if (file_exists($fullImagePath)) {
                    $section->addImage($fullImagePath, [
                        'width' => 450,
                        'ratio' => true,
                        'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::START
                    ]);
                } else {
                    $section->addText(' [File tidak ditemukan di server]');
                }
            } else {
                $section->addText(' [Tidak ada lampiran gambar]');
            }

            // Garis pembatas hitam antar temuan
            $section->addTextBreak(1);
            $section->addLine([
                'weight' => 1, 
                'width' => 450, 
                'height' => 0, 
                'color' => '000000'
            ]);
            $section->addTextBreak(1);
        }

        // 6. Simpan File Sementara & Beri Respons Download
        $fileName = 'QA_Report_' . $report->project->name . '_' . date('Ymd_His') . '.docx';
        $tempFile = storage_path('app/public/' . $fileName);

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        $downloadUrl = asset('storage/' . $fileName);

        return response()->json([
            'status' => 'success',
            'message' => 'File berhasil dibuat',
            'download_url' => $downloadUrl
        ]);
    }

    /**
     * Memperbarui status spesifik pada satu baris temuan (Bug Lifecycle)
     */
    public function updateFindingStatus(Request $request, $id)
    {
        // 1. Validasi input yang masuk (hanya butuh status baru)
        $request->validate([
            'status' => 'required|string|in:Lulus,Gagal'
        ]);

        // 2. Cari data temuan berdasarkan ID, jika tidak ada akan otomatis 404
        $finding = \App\Models\Finding::findOrFail($id);

        // 3. Timpa status lama dengan status baru
        $finding->update([
            'status' => $request->status
        ]);

        // 4. Berikan respons sukses ke frontend
        return response()->json([
            'status' => 'success',
            'message' => 'Status bug berhasil diperbarui menjadi ' . $request->status,
            'data' => $finding
        ], 200);
    }
}