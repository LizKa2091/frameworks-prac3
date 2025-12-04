@extends('layouts.app')

@section('content')
<div class="container py-3">
  <h3 class="mb-3">NASA OSDR</h3>
  <div class="small text-muted mb-2">Источник {{ $src }}</div>
  <div class="filter-controls mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small">Поиск по ключевым словам</label>
        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Поиск...">
      </div>
      <div class="col-md-3">
        <label class="form-label small">Столбец для сортировки</label>
        <select id="sortColumn" class="form-select form-select-sm">
          <option value="id">ID</option>
          <option value="dataset_id">dataset_id</option>
          <option value="title">title</option>
          <option value="updated_at" selected>updated_at</option>
          <option value="inserted_at">inserted_at</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small">Направление</label>
        <select id="sortDirection" class="form-select form-select-sm">
          <option value="asc">По возрастанию</option>
          <option value="desc" selected>По убыванию</option>
        </select>
      </div>
      <div class="col-md-3">
        <button type="button" id="resetFilters" class="btn btn-sm btn-outline-secondary">Сбросить</button>
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-sm table-striped align-middle" id="osdrTable">
      <thead>
        <tr>
          <th class="sortable-header" data-column="id">#</th>
          <th class="sortable-header" data-column="dataset_id">dataset_id</th>
          <th class="sortable-header" data-column="title">title</th>
          <th>REST_URL</th>
          <th class="sortable-header" data-column="updated_at">updated_at</th>
          <th class="sortable-header" data-column="inserted_at">inserted_at</th>
          <th>raw</th>
        </tr>
      </thead>
      <tbody id="tableBody">
      @forelse($items as $row)
        <tr data-row='@json($row)'>
          <td>{{ $row['id'] }}</td>
          <td>{{ $row['dataset_id'] ?? '—' }}</td>
          <td style="max-width:420px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            {{ $row['title'] ?? '—' }}
          </td>
          <td>
            @if(!empty($row['rest_url']))
              <a href="{{ $row['rest_url'] }}" target="_blank" rel="noopener">открыть</a>
            @else — @endif
          </td>
          <td>{{ $row['updated_at'] ?? '—' }}</td>
          <td>{{ $row['inserted_at'] ?? '—' }}</td>
          <td>
            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#raw-{{ $row['id'] }}-{{ md5($row['dataset_id'] ?? (string)$row['id']) }}">JSON</button>
          </td>
        </tr>
        <tr class="collapse" id="raw-{{ $row['id'] }}-{{ md5($row['dataset_id'] ?? (string)$row['id']) }}">
          <td colspan="7">
            <pre class="mb-0" style="max-height:260px;overflow:auto">{{ json_encode($row['raw'] ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) }}</pre>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-center text-muted">нет данных</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('searchInput');
  const sortColumn = document.getElementById('sortColumn');
  const sortDirection = document.getElementById('sortDirection');
  const resetFilters = document.getElementById('resetFilters');
  const tableBody = document.getElementById('tableBody');
  const sortableHeaders = document.querySelectorAll('.sortable-header');
  
  let allRows = Array.from(tableBody.querySelectorAll('tr[data-row]')).map(tr => ({
    element: tr,
    data: JSON.parse(tr.getAttribute('data-row'))
  }));

  function filterAndSort() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    const column = sortColumn.value;
    const direction = sortDirection.value;

    // Фильтрация
    let filtered = allRows.filter(row => {
      if (!searchTerm) return true;
      const data = row.data;
      const searchable = [
        String(data.id || ''),
        String(data.dataset_id || ''),
        String(data.title || ''),
        String(data.updated_at || ''),
        String(data.inserted_at || '')
      ].join(' ').toLowerCase();
      return searchable.includes(searchTerm);
    });

    // Сортировка
    filtered.sort((a, b) => {
      let aVal = a.data[column];
      let bVal = b.data[column];
      
      // Обработка дат
      if (column === 'updated_at' || column === 'inserted_at') {
        aVal = aVal ? new Date(aVal).getTime() : 0;
        bVal = bVal ? new Date(bVal).getTime() : 0;
      } else if (typeof aVal === 'string') {
        aVal = aVal.toLowerCase();
        bVal = (bVal || '').toLowerCase();
      }
      
      if (aVal < bVal) return direction === 'asc' ? -1 : 1;
      if (aVal > bVal) return direction === 'asc' ? 1 : -1;
      return 0;
    });

    // Обновление таблицы
    tableBody.innerHTML = '';
    if (filtered.length === 0) {
      tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">нет данных</td></tr>';
    } else {
      filtered.forEach(row => {
        tableBody.appendChild(row.element);
      });
    }

    // Обновление заголовков сортировки
    sortableHeaders.forEach(header => {
      header.classList.remove('asc', 'desc');
      if (header.getAttribute('data-column') === column) {
        header.classList.add(direction);
      }
    });
  }

  // Обработчики событий
  searchInput.addEventListener('input', filterAndSort);
  sortColumn.addEventListener('change', filterAndSort);
  sortDirection.addEventListener('change', filterAndSort);
  
  sortableHeaders.forEach(header => {
    header.addEventListener('click', function() {
      const column = this.getAttribute('data-column');
      sortColumn.value = column;
      if (this.classList.contains('asc')) {
        sortDirection.value = 'desc';
      } else {
        sortDirection.value = 'asc';
      }
      filterAndSort();
    });
  });

  resetFilters.addEventListener('click', function() {
    searchInput.value = '';
    sortColumn.value = 'updated_at';
    sortDirection.value = 'desc';
    filterAndSort();
  });
});
</script>
@endsection
