<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('QA Monitoring Center - Bug Lifecycle') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <!-- FASE 10: KARTU METRIK & GRAFIK -->
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                <!-- 3 Kartu Ringkasan (Cards) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg shadow-sm">
                        <h4 class="text-sm font-bold text-blue-600 uppercase">Total Temuan (Bugs)</h4>
                        <p class="text-3xl font-extrabold text-blue-800 mt-1">{{ $metrics['total_bugs'] }}</p>
                    </div>
                    <div class="bg-red-50 border border-red-200 p-4 rounded-lg shadow-sm">
                        <h4 class="text-sm font-bold text-red-600 uppercase">Open / Gagal (Need Fix)</h4>
                        <p class="text-3xl font-extrabold text-red-800 mt-1">{{ $metrics['status_gagal'] }}</p>
                    </div>
                    <div class="bg-green-50 border border-green-200 p-4 rounded-lg shadow-sm">
                        <h4 class="text-sm font-bold text-green-600 uppercase">Closed / Lulus (Fixed)</h4>
                        <p class="text-3xl font-extrabold text-green-800 mt-1">{{ $metrics['status_lulus'] }}</p>
                    </div>
                </div>

                <!-- Kanvas Grafik Chart.js -->
                <div class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50 flex flex-col md:flex-row items-center gap-6">
                    <div class="w-full md:w-1/3">
                        <h3 class="text-lg font-bold text-gray-800 mb-2">Sebaran Severity Bug</h3>
                        <p class="text-sm text-gray-600 mb-4">Grafik ini memvisualisasikan tingkat keparahan (Severity) dari seluruh bug yang tercatat di dalam sistem.</p>
                    </div>
                    <div class="w-full md:w-2/3 h-48 relative flex justify-center">
                        <canvas id="severityChart"></canvas>
                    </div>
                </div>
                <!-- AKHIR FASE 10 -->

                <div class="flex justify-between items-center mb-6 pt-4 border-t">
                    <h3 class="text-lg font-bold text-gray-800">Daftar Pelacakan Temuan Bug</h3>
                    
                    <!-- Form Pencarian (Fase 9 Lanjutan) -->
                    <form method="GET" action="{{ route('dashboard') }}" class="flex gap-2">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari project atau bug..." class="p-2 border rounded-md text-sm">
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-bold rounded hover:bg-gray-700 transition">Cari</button>
                    </form>

                    <a href="{{ url('/') }}" class="px-4 py-2 bg-blue-600 text-white font-bold rounded hover:bg-blue-700 transition">
                        + Buat Laporan Baru
                    </a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="py-3 px-4 border-b font-semibold text-sm text-left">Project</th>
                                <th class="py-3 px-4 border-b font-semibold text-sm text-left">Deskripsi Temuan (Bug)</th>
                                <th class="py-3 px-4 border-b font-semibold text-sm text-center">Severity</th>
                                <th class="py-3 px-4 border-b font-semibold text-sm text-center">Status Lifecycle</th>
                                <th class="py-3 px-4 border-b font-semibold text-sm text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reports as $report)
                                @php $findingsCount = $report->findings->count(); @endphp
                                
                                @foreach($report->findings as $finding)
                                    <tr class="hover:bg-gray-50 transition border-b">
                                        @if($loop->first)
                                            <td class="py-3 px-4 text-sm text-gray-700 align-middle border-r bg-white" rowspan="{{ $findingsCount }}">
                                                <span class="font-bold text-base">{{ $report->project->name }}</span>
                                                <br><span class="text-xs text-gray-400">ID Laporan: #{{ $report->id }}</span>
                                            </td>
                                        @endif
                                        
                                        <td class="py-3 px-4 text-sm font-medium text-gray-800">{{ $finding->description }}</td>
                                        <td class="py-3 px-4 text-sm text-center">{{ $finding->severity ?? '-' }}</td>
                                        
                                        <td class="py-3 px-4 text-center">
                                            <select 
                                                class="status-dropdown py-1.5 pl-3 pr-8 border rounded text-sm font-bold cursor-pointer transition-colors focus:ring-0
                                                {{ $finding->status == 'Lulus' ? 'bg-green-100 text-green-700 border-green-300' : 'bg-red-100 text-red-700 border-red-300' }}"
                                                data-id="{{ $finding->id }}"
                                            >
                                                <option value="Gagal" {{ $finding->status == 'Gagal' ? 'selected' : '' }}>Gagal</option>
                                                <option value="Lulus" {{ $finding->status == 'Lulus' ? 'selected' : '' }}>Lulus</option>
                                            </select>
                                        </td>

                                        @if($loop->first)
                                            <td class="py-3 px-4 text-center align-middle border-l bg-white" rowspan="{{ $findingsCount }}">
                                                <div class="flex flex-col gap-2 px-2">
                                                    <button class="download-report-btn px-4 py-2 bg-blue-500 text-white text-xs font-bold rounded shadow hover:bg-blue-600 transition w-full" data-id="{{ $report->id }}">
                                                        Download Word
                                                    </button>
                                                    <button class="delete-report-btn px-4 py-2 bg-red-500 text-white text-xs font-bold rounded shadow hover:bg-red-600 transition w-full" data-id="{{ $report->id }}">
                                                        Hapus Laporan
                                                    </button>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-gray-500 italic">Belum ada laporan QA yang masuk.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Links -->
                <div class="mt-4">
                    {{ $reports->links() }}
                </div>

            </div>
        </div>
    </div>

    <!-- JavaScript Gabungan -->
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // 1. RENDER CHART.JS (Fase 10)
        const ctx = document.getElementById('severityChart').getContext('2d');
        const severityChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Tinggi (Kritis)', 'Sedang', 'Rendah'],
                datasets: [{
                    data: [
                        {{ $metrics['severity_tinggi'] }}, 
                        {{ $metrics['severity_sedang'] }}, 
                        {{ $metrics['severity_rendah'] }}
                    ],
                    backgroundColor: ['#ef4444', '#eab308', '#3b82f6'],
                    borderWidth: 1,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });

        // 2. UPDATE STATUS
        document.querySelectorAll('.status-dropdown').forEach(dropdown => {
            dropdown.addEventListener('change', async function() {
                const findingId = this.getAttribute('data-id');
                const newStatus = this.value;
                const originalClass = this.className;

                if(newStatus === 'Lulus') {
                    this.className = "status-dropdown py-1.5 pl-3 pr-8 border rounded text-sm font-bold cursor-pointer transition-colors focus:ring-0 bg-green-100 text-green-700 border-green-300";
                } else {
                    this.className = "status-dropdown py-1.5 pl-3 pr-8 border rounded text-sm font-bold cursor-pointer transition-colors focus:ring-0 bg-red-100 text-red-700 border-red-300";
                }

                try {
                    const response = await fetch(`/findings/${findingId}/status`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ status: newStatus })
                    });
                    
                    if(response.ok) {
                        window.location.reload(); 
                    } else {
                        const result = await response.json();
                        alert('Gagal memperbarui status: ' + result.message);
                        this.className = originalClass; 
                    }
                } catch (error) {
                    alert('Terjadi kesalahan jaringan.');
                    this.className = originalClass; 
                }
            });
        });

        // 3. DOWNLOAD & HAPUS
        document.querySelectorAll('.delete-report-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                if(confirm('Yakin ingin menghapus seluruh laporan ini?')) {
                    const reportId = this.getAttribute('data-id');
                    this.innerText = 'Menghapus...';
                    this.disabled = true;
                    this.classList.add('opacity-50');

                    try {
                        const response = await fetch(`/qa-reports/${reportId}`, {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                        });
                        if(response.ok) {
                            window.location.reload();
                        } else {
                            alert('Gagal menghapus laporan.');
                            this.innerText = 'Hapus Laporan';
                            this.disabled = false;
                            this.classList.remove('opacity-50');
                        }
                    } catch (error) {
                        alert('Terjadi kesalahan jaringan.');
                        this.innerText = 'Hapus Laporan';
                        this.disabled = false;
                        this.classList.remove('opacity-50');
                    }
                }
            });
        });

        document.querySelectorAll('.download-report-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const reportId = this.getAttribute('data-id');
                const originalText = this.innerText;
                this.innerText = 'Memproses...';
                this.disabled = true;
                this.classList.add('opacity-50');

                try {
                    const response = await fetch(`/qa-reports/${reportId}/export`, {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                    });
                    const result = await response.json();
                    if(response.ok) {
                        window.location.href = result.download_url;
                    } else {
                        alert('Gagal mengunduh: ' + (result.message || 'Error'));
                    }
                } catch (error) {
                    alert('Terjadi kesalahan jaringan.');
                } finally {
                    this.innerText = originalText;
                    this.disabled = false;
                    this.classList.remove('opacity-50');
                }
            });
        });
    </script>
</x-app-layout>