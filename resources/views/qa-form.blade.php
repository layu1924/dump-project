<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>QA Automated Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10 font-sans">

    <div class="max-w-6xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <!-- Header User -->
        <div class="flex justify-between items-center mb-6 pb-4 border-b">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Form Laporan QA</h1>
                <p class="text-gray-500">Halo, <span class="font-bold text-blue-600">{{ auth()->user()->name }}</span>! Lampirkan bukti bug dan generate Word.</p>
            </div>
            
            <!-- Tambahan Navigasi -->
            <div class="flex items-center gap-4">
                <!-- Tombol ke Dashboard -->
                <a href="{{ route('dashboard') }}" class="text-sm font-bold text-blue-600 hover:text-blue-800 underline underline-offset-2 transition">
                    Kembali
                </a>
                
                <span class="text-gray-300">|</span>

                <!-- Tombol Logout bawaan Laravel -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm font-bold text-red-500 hover:text-red-700 transition">Logout</button>
                </form>
            </div>
        </div>

        <form id="qaForm" class="space-y-6" enctype="multipart/form-data">
            <!-- Data Utama -->
            <div class="bg-gray-50 p-4 rounded border">
                <label class="block text-sm font-semibold text-gray-700">Nama Project</label>
                
                <!-- Menggunakan datalist agar bisa memilih dari dropdown ATAU mengetik project baru -->
                <input list="project-list" type="text" id="projectName" class="mt-1 p-2 w-full md:w-1/2 border rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="Pilih Project atau Ketik Baru..." required autocomplete="off">
                
                <datalist id="project-list">
                    @foreach($projects as $project)
                        <option value="{{ $project->name }}"></option>
                    @endforeach
                </datalist>
                
            </div>

            <!-- Data Temuan (Findings) -->
            <div>
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-xl font-semibold text-gray-800">Daftar Temuan (Findings)</h2>
                    <button type="button" id="addFindingBtn" class="px-3 py-1 bg-blue-100 text-blue-700 text-sm font-bold rounded hover:bg-blue-200 transition">+ Tambah Baris</button>
                </div>
                
                <div id="findingsContainer" class="space-y-3">
                    <!-- Template Baris Temuan -->
                    <div class="finding-row flex flex-col md:flex-row gap-2 bg-white p-3 rounded border border-gray-200 shadow-sm items-center">
                        <input type="text" class="desc p-2 flex-1 border rounded" placeholder="Deskripsi Bug/Temuan..." required>
                        <select class="status p-2 border rounded md:w-32 bg-gray-50">
                            <option value="Gagal">Gagal</option>
                            <option value="Lulus">Lulus</option>
                        </select>
                        <select class="severity p-2 border rounded md:w-32 bg-gray-50">
                            <option value="Tinggi">Tinggi</option>
                            <option value="Sedang">Sedang</option>
                            <option value="Rendah">Rendah</option>
                        </select>
                        <!-- FITUR BARU: Input File Gambar -->
                        <input type="file" class="image-file p-2 border rounded md:w-56 text-sm text-gray-600 file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="image/png, image/jpeg, image/jpg">
                        
                        <button type="button" class="remove-btn px-3 py-2 bg-red-100 text-red-600 font-bold rounded hover:bg-red-200 transition" title="Hapus Baris">X</button>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t flex items-center justify-between">
                <span id="loadingMsg" class="text-blue-600 font-semibold hidden animate-pulse">Memproses laporan & mengunggah gambar...</span>
                <button type="submit" id="submitBtn" class="ml-auto px-6 py-3 bg-green-600 text-white font-bold rounded shadow hover:bg-green-700 transition">
                    Kirim & Download Word
                </button>
            </div>
        </form>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // PERHATIAN: Content-Type dihapus! Browser akan otomatis mengaturnya menjadi 'multipart/form-data' saat mendeteksi objek FormData.
        const fetchHeaders = {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken 
        };

        // 1. Tambah Baris
        document.getElementById('addFindingBtn').addEventListener('click', function() {
            const container = document.getElementById('findingsContainer');
            const row = document.querySelector('.finding-row').cloneNode(true);
            row.querySelector('.desc').value = ''; 
            row.querySelector('.image-file').value = ''; // Kosongkan file di baris kloning
            container.appendChild(row);
        });

        // 2. Hapus Baris
        document.getElementById('findingsContainer').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-btn')) {
                const totalRows = document.querySelectorAll('.finding-row').length;
                if (totalRows > 1) {
                    e.target.closest('.finding-row').remove();
                } else {
                    alert('Minimal 1 temuan pengujian!');
                }
            }
        });

        // 3. Submit Form (Diubah menggunakan FormData)
        document.getElementById('qaForm').addEventListener('submit', async function(e) {
            e.preventDefault(); 
            
            const submitBtn = document.getElementById('submitBtn');
            const loadingMsg = document.getElementById('loadingMsg');
            
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50');
            loadingMsg.classList.remove('hidden');

            // Inisialisasi Objek FormData
            const formData = new FormData();
            formData.append('project_name', document.getElementById('projectName').value);

            // Masukkan data setiap baris secara dinamis agar terbaca sebagai Array di PHP
            document.querySelectorAll('.finding-row').forEach((row, index) => {
                formData.append(`findings[${index}][description]`, row.querySelector('.desc').value);
                formData.append(`findings[${index}][status]`, row.querySelector('.status').value);
                formData.append(`findings[${index}][severity]`, row.querySelector('.severity').value);

                // Jika QA melampirkan gambar, masukkan filenya
                const fileInput = row.querySelector('.image-file');
                if (fileInput.files.length > 0) {
                    formData.append(`findings[${index}][image]`, fileInput.files[0]);
                }
            });

            try {
                const postResponse = await fetch('/qa-reports', {
                    method: 'POST',
                    headers: fetchHeaders,
                    body: formData // Mengirim FormData, bukan JSON.stringify
                });
                
                const postResult = await postResponse.json();

                if(postResponse.status === 201) {
                    const newReportId = postResult.data.id;
                    
                    const getResponse = await fetch(`/qa-reports/${newReportId}/export`, {
                        headers: fetchHeaders
                    });
                    const getResult = await getResponse.json();

                    if(getResponse.status === 200) {
                        window.location.href = getResult.download_url;
                        alert('Berhasil! Dokumen atas nama ' + '{{ auth()->user()->name }}' + ' sedang diunduh.');
                        
                        document.getElementById('qaForm').reset(); 
                        const rows = document.querySelectorAll('.finding-row');
                        for(let i = 1; i < rows.length; i++) {
                            rows[i].remove();
                        }
                    } else {
                        alert('Gagal generate Word: ' + getResult.message);
                    }
                } else {
                    alert('Gagal menyimpan laporan. Cek format gambar atau ukurannya (Maks 2MB).');
                    console.error(postResult);
                }

            } catch (error) {
                alert('Terjadi kesalahan jaringan.');
                console.error(error);
            } finally {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50');
                loadingMsg.classList.add('hidden');
            }
        });
    </script>
</body>
</html>