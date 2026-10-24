/**
 * js/table-sort.js
 * Simple vanilla JS table sorter.
 * - Click any table header to sort that column.
 * - Detects dates (dd/mm/yyyy, yyyy-mm-dd) and numbers.
 */
(function () {
  'use strict';

  function parseDateString(s) {
    if (!s) return null;
    s = s.trim();
    // dd/mm/yyyy or d/m/yy
    var dmy = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})$/);
    if (dmy) {
      var day = parseInt(dmy[1], 10);
      var month = parseInt(dmy[2], 10) - 1;
      var year = parseInt(dmy[3], 10);
      if (year < 100) year += 2000;
      return new Date(year, month, day).getTime();
    }

    // yyyy-mm-dd or yyyy/mm/dd
    var ymd = s.match(/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/);
    if (ymd) {
      return new Date(parseInt(ymd[1], 10), parseInt(ymd[2], 10) - 1, parseInt(ymd[3], 10)).getTime();
    }

    return null;
  }

  function detectNumber(s) {
    if (!s) return NaN;
    var cleaned = s.replace(/[^0-9\-\.,]/g, '').replace(/\s/g, '');
    if (cleaned.indexOf(',') !== -1 && cleaned.indexOf('.') === -1) {
      cleaned = cleaned.replace(/,/g, '.');
    } else {
      cleaned = cleaned.replace(/,/g, '');
    }
    var val = parseFloat(cleaned);
    return isNaN(val) ? NaN : val;
  }

  function initTableSorters() {
    var tables = document.querySelectorAll('table');
    tables.forEach(function (table) {
      var thead = table.tHead || table.querySelector('thead');
      if (!thead) return;
      var headerRow = thead.rows[0];
      if (!headerRow) return;

      Array.from(headerRow.cells).forEach(function (th, colIndex) {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function () {
          var rows;
          if (table.tBodies && table.tBodies.length) {
            rows = Array.from(table.tBodies[0].rows);
          } else {
            var allRows = Array.from(table.rows);
            var start = headerRow.rowIndex + 1;
            rows = allRows.slice(start);
          }
          if (!rows.length) return;

          var dir = th.getAttribute('data-sort-dir') === 'asc' ? 'desc' : 'asc';

          // Clear previous sort markers
          Array.from(headerRow.cells).forEach(function (h) {
            h.removeAttribute('data-sort-dir');
            h.classList.remove('ts-sorted-header');
          });
          // remove any cell highlight classes
          var prev = table.querySelectorAll('td.ts-sorted');
          prev.forEach(function (c) { c.classList.remove('ts-sorted'); });

          // set new sort marker
          th.setAttribute('data-sort-dir', dir);
          th.classList.add('ts-sorted-header');
          table.setAttribute('data-sorted-col', String(colIndex + 1));
          table.setAttribute('data-sorted-dir', dir);

          rows.sort(function (a, b) {
            var A = (a.cells[colIndex] ? a.cells[colIndex].innerText : '').trim();
            var B = (b.cells[colIndex] ? b.cells[colIndex].innerText : '').trim();

            var Adt = parseDateString(A);
            var Bdt = parseDateString(B);
            if (Adt !== null && Bdt !== null) {
              return dir === 'asc' ? Adt - Bdt : Bdt - Adt;
            }

            var An = detectNumber(A);
            var Bn = detectNumber(B);
            if (!isNaN(An) && !isNaN(Bn)) {
              return dir === 'asc' ? An - Bn : Bn - An;
            }

            return dir === 'asc'
              ? A.localeCompare(B, undefined, {numeric: true, sensitivity: 'base'})
              : B.localeCompare(A, undefined, {numeric: true, sensitivity: 'base'});
          });

          if (table.tBodies && table.tBodies.length) {
            rows.forEach(function (r) { table.tBodies[0].appendChild(r); });
          } else {
            rows.forEach(function (r) { table.appendChild(r); });
          }

          // Add highlight class to each cell in the sorted column
          rows.forEach(function (r) {
            if (r.cells[colIndex]) r.cells[colIndex].classList.add('ts-sorted');
          });
        }, false);
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTableSorters);
  } else {
    initTableSorters();
  }
})();
