@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h3 class="mb-3">Визуализация CSV файлов (Legacy)</h3>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <h5 class="card-title">Доступные CSV файлы</h5>
      <div class="list-group">
        @forelse($files as $file)
          <a href="#" class="list-group-item list-group-item-action csv-file-link" data-file="{{ $file }}">
            {{ $file }}
          </a>
        @empty
          <div class="text-muted">CSV файлы не найдены</div>
        @endforelse
      </div>
    </div>
  </div>

  <div class="card shadow-sm" id="csvTableCard" style="display:none">
    <div class="card-body">
      <h5 class="card-title" id="csvFileName"></h5>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle" id="csvTable">
          <thead id="csvTableHead"></thead>
          <tbody id="csvTableBody"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const fileLinks = document.querySelectorAll('.csv-file-link');
  const tableCard = document.getElementById('csvTableCard');
  const fileName = document.getElementById('csvFileName');
  const tableHead = document.getElementById('csvTableHead');
  const tableBody = document.getElementById('csvTableBody');
  
  fileLinks.forEach(link => {
    link.addEventListener('click', async function(e) {
      e.preventDefault();
      const file = this.getAttribute('data-file');
      fileName.textContent = file;
      
      try {
        const response = await fetch('/legacy/csv/content?file=' + encodeURIComponent(file));
        const data = await response.json();
        
        // Заголовки
        tableHead.innerHTML = '<tr>' + data.headers.map(h => '<th>' + h + '</th>').join('') + '</tr>';
        
        // Данные
        tableBody.innerHTML = data.rows.map(row => {
          return '<tr>' + data.headers.map(header => {
            const value = row[header] || '';
            // Форматирование для разных типов данных
            if (header === 'recorded_at' || header.includes('_at')) {
              return '<td><code>' + value + '</code></td>';
            } else if (header === 'is_active' || header === 'status') {
              const isTrue = value === 'ИСТИНА'  value === 'true'  value === '1';
              return '<td><span class="badge ' + (isTrue ? 'bg-success' : 'bg-secondary') + '">' + value + '</span></td>';
            } else if (header === 'voltage' || header === 'temp') {
              return '<td class="text-end">' + parseFloat(value).toFixed(2) + '</td>';
            } else {
              return '<td>' + value + '</td>';
            }
          }).join('') + '</tr>';
        }).join('');
        
        tableCard.style.display = 'block';
        tableCard.scrollIntoView({ behavior: 'smooth' });
      } catch (error) {
        alert('Ошибка загрузки файла: ' + error.message);
      }
    });
  });
});
</script>
@endsection