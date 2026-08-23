<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $title }} - PDF</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      color: #111827;
      margin: 28px;
      font-size: 12px;
    }

    .head {
      display: flex;
      justify-content: space-between;
      gap: 16px;
      border-bottom: 2px solid #111827;
      padding-bottom: 12px;
      margin-bottom: 16px;
    }

    h1 {
      font-size: 20px;
      margin: 0 0 6px;
    }

    .muted {
      color: #6b7280;
    }

    .stats {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
      margin-bottom: 16px;
    }

    .stat {
      border: 1px solid #d1d5db;
      padding: 10px;
      border-radius: 8px;
    }

    .stat span {
      display: block;
      color: #6b7280;
      font-size: 10px;
      text-transform: uppercase;
      font-weight: bold;
      margin-bottom: 6px;
    }

    .stat strong {
      font-size: 20px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      border: 1px solid #d1d5db;
      padding: 7px;
      text-align: left;
      vertical-align: top;
    }

    th {
      background: #f3f4f6;
      font-size: 10px;
      text-transform: uppercase;
    }

    @media print {
      body {
        margin: 12mm;
      }

      .no-print {
        display: none;
      }
    }
  </style>
</head>
<body>
  <button class="no-print" onclick="window.print()" style="margin-bottom:16px;padding:8px 12px">Cetak / Simpan PDF</button>
  <div class="head">
    <div>
      <h1>{{ $title }}</h1>
      <div class="muted">Laporan Konfirmasi Kehadiran Yudisium Fakultas Teknik</div>
    </div>
    <div class="muted">
      Dibuat: {{ $generatedAt->format('d/m/Y H:i') }}<br>
      Total baris: {{ number_format($rows->count()) }}
    </div>
  </div>

  <div class="stats">
    <div class="stat"><span>Total</span><strong>{{ number_format($summary['total']) }}</strong></div>
    <div class="stat"><span>Hadir</span><strong>{{ number_format($summary['attending']) }}</strong></div>
    <div class="stat"><span>Berhalangan</span><strong>{{ number_format($summary['declined']) }}</strong></div>
    <div class="stat"><span>Diwakilkan</span><strong>{{ number_format($summary['represented']) }}</strong></div>
    <div class="stat"><span>{{ $type === 'mahasiswa' ? 'Check-in' : 'Belum' }}</span><strong>{{ number_format($type === 'mahasiswa' ? $summary['checked_in'] : $summary['pending']) }}</strong></div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Nama</th>
        @if ($type === 'mahasiswa')
          <th>No/NIM</th>
          <th>Prodi</th>
        @else
          <th>Kategori</th>
          <th>Keterangan</th>
        @endif
        <th>Konfirmasi Kehadiran</th>
        <th>Waktu Konfirmasi</th>
        @if ($type === 'mahasiswa')
          <th>Check-in</th>
        @endif
        <th>Catatan</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($rows as $row)
        <tr>
          <td>{{ $row['name'] }}</td>
          @if ($type === 'mahasiswa')
            <td>{{ $row['sequence_number'] ?: '-' }} / {{ $row['nim'] ?: '-' }}</td>
            <td>{{ $row['context'] }}</td>
          @else
            <td>{{ $row['category'] }}</td>
            <td>{{ $row['context'] }}</td>
          @endif
          <td>{{ $row['rsvp_label'] }}</td>
          <td>{{ $row['responded_at_label'] }}</td>
          @if ($type === 'mahasiswa')
            <td>{{ $row['checked_in'] ? 'Sudah check-in' : 'Belum check-in' }}<br>{{ $row['checked_in_at_label'] }}</td>
          @endif
          <td>{{ $row['note'] ?: '-' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <script>
    window.addEventListener("load", () => window.setTimeout(() => window.print(), 350));
  </script>
</body>
</html>
