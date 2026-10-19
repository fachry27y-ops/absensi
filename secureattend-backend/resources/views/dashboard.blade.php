<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureAttend Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800">
<div class="max-w-7xl mx-auto py-10 px-4">
    <header class="flex items-center justify-between mb-8">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-indigo-600">SecureAttend</p>
            <h1 class="text-3xl font-bold">Dashboard Admin</h1>
        </div>
        <span class="bg-indigo-600 text-white px-4 py-2 rounded-full text-sm font-medium">Asia/Jakarta</span>
    </header>

    <section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <p class="text-sm text-slate-500">Karyawan Aktif</p>
            <h2 class="text-3xl font-bold mt-2">{{ $stats['employees'] ?? 0 }}</h2>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <p class="text-sm text-slate-500">Hadir Hari Ini</p>
            <h2 class="text-3xl font-bold mt-2">{{ $stats['present'] ?? 0 }}</h2>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <p class="text-sm text-slate-500">Terlambat</p>
            <h2 class="text-3xl font-bold mt-2">{{ $stats['late'] ?? 0 }}</h2>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <p class="text-sm text-slate-500">Lokasi Aktif</p>
            <h2 class="text-3xl font-bold mt-2">{{ $stats['locations'] ?? 0 }}</h2>
        </div>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-6 shadow-sm overflow-hidden">
            <h3 class="font-semibold text-lg mb-4">Kehadiran Bulanan</h3>
            <div class="h-72 w-full">
                <canvas id="attendanceChart" class="w-full h-full"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h3 class="font-semibold text-lg mb-4">Status Terbaru</h3>
            <ul class="space-y-3">
                @foreach($recent as $item)
                    <li class="flex items-center justify-between border-b border-slate-100 pb-3 last:border-none last:pb-0">
                        <div>
                            <p class="font-medium">{{ $item['name'] }}</p>
                            <p class="text-sm text-slate-500">{{ $item['status'] }}</p>
                        </div>
                        <span class="text-sm text-slate-500">{{ $item['time'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="bg-white rounded-2xl p-6 shadow-sm">
        <h3 class="font-semibold text-lg mb-4">Daftar Lokasi Kerja</h3>
        <div class="overflow-auto max-h-[420px] border border-slate-100 rounded-xl">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Cabang</th>
                    <th class="px-4 py-3">Radius</th>
                    <th class="px-4 py-3">GPS Max</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
                </thead>
                <tbody>
                @foreach($locations as $location)
                    <tr class="border-b border-slate-100 last:border-none">
                        <td class="px-4 py-3">{{ $location->name }}</td>
                        <td class="px-4 py-3">{{ $location->branch_name }}</td>
                        <td class="px-4 py-3">{{ $location->radius_meters }} m</td>
                        <td class="px-4 py-3">{{ $location->max_accuracy_meters }} m</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $location->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                {{ $location->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
    const ctx = document.getElementById('attendanceChart');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
            datasets: [{
                label: 'Kehadiran',
                data: [76, 82, 88, 81, 94, 90],
                backgroundColor: '#6366f1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { stepSize: 20 }
                },
                x: {
                    ticks: { autoSkip: false }
                }
            }
        }
    });
</script>
</body>
</html>
