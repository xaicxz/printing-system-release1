'use strict';
(() => {
  const $ = (id) => document.getElementById(id);
  const menu = $('menu-toggle');
  const sidebar = $('sidebar');
  const overlay = $('mobile-overlay');
  if (menu && sidebar && overlay) {
    menu.addEventListener('click', () => {sidebar.classList.toggle('open'); overlay.classList.toggle('open');});
    overlay.addEventListener('click', () => {sidebar.classList.remove('open'); overlay.classList.remove('open');});
  }
  document.querySelectorAll('[data-step]').forEach((button) => button.addEventListener('click', () => {
    const input = $(button.dataset.target);
    if (!input) return;
    const min = Number(input.min || 1), max = Number(input.max || 10000);
    const next = Number(input.value || min) + Number(button.dataset.step);
    input.value = String(Math.min(max, Math.max(min, next)));
    input.dispatchEvent(new Event('input', {bubbles: true}));
  }));
  document.querySelectorAll('form[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
  }));
  document.querySelectorAll('[data-reveal]').forEach((btn) => btn.addEventListener('click', () => {
    const panel = $(btn.dataset.reveal);
    if (panel) panel.classList.toggle('hidden');
  }));
  const form = $('print-form');
  if (!form) return;
  const rates = JSON.parse(form.dataset.rates || '[]');
  const pdfInput = $('pdf');
  const pages = $('pages');
  const copies = $('copies');
  const submit = $('submit-order');
  let reading = false;
  const peso = (value) => '₱' + value.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  const selected = (name) => form.querySelector(`input[name="${name}"]:checked`)?.value;
  const rateFor = (paper, color) => rates.find((r) => r.paper_size === paper && r.print_color === color);
  function updateQuote() {
    const paper = selected('paper_size'), color = selected('print_color'), sides = selected('sides');
    const rate = rateFor(paper, color);
    const count = Number(pages.value), copyCount = Number(copies.value);
    const valid = pdfInput.files.length === 1 && count >= 1 && count <= 10000 && Number.isInteger(count) && copyCount >= 1 && copyCount <= 100 && Number.isInteger(copyCount) && !!rate && !reading;
    $('sum-paper').textContent = paper || '—';
    $('sum-color').textContent = color === 'color' ? 'Full color' : 'Black & white';
    $('sum-sides').textContent = sides === 'double' ? 'Double-sided' : 'Single-sided';
    $('sum-quantity').textContent = count > 0 ? `${count} × ${copyCount || 0}` : '—';
    $('sum-rate').textContent = rate ? peso(Number(rate.price_per_page)) : 'Unavailable';
    $('sum-total').textContent = rate && count > 0 && copyCount > 0 ? peso(Number(rate.price_per_page) * count * copyCount) : '₱0.00';
    submit.disabled = !valid;
  }
  form.addEventListener('input', updateQuote);
  form.addEventListener('change', updateQuote);
  async function processPDF() {
    const f = pdfInput.files[0];
    $('upload-drop').classList.toggle('has-file', !!f);
    $('file-name').textContent = f ? f.name : 'Click to choose a PDF';
    $('file-meta').textContent = f ? `${(f.size / 1024 / 1024).toFixed(2)} MB` : '';
    if (!f) {pages.value=''; updateQuote(); return;}
    if (f.size > Number(form.dataset.max) || !/\.pdf$/i.test(f.name)) {
      $('pages-help').textContent = 'Choose a valid PDF under 15 MB.';
      pages.value = ''; updateQuote(); return;
    }
    reading = true;
    pages.value = '';
    pages.placeholder = 'Checking file…';
    $('page-mode').textContent = 'Detecting…';
    updateQuote();
    try {
      if (!window.pdfjsLib) throw new Error('Page detector unavailable');
      window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
      const data = new Uint8Array(await f.arrayBuffer());
      const doc = await window.pdfjsLib.getDocument({data}).promise;
      pages.value = String(doc.numPages);
      $('pages-help').textContent = 'Detected from your PDF. Please check before submitting.';
      $('page-mode').textContent = 'Auto-detected';
      if (typeof doc.destroy === 'function') await doc.destroy();
    } catch (err) {
      pages.placeholder = 'e.g. 5';
      $('pages-help').textContent = 'Automatic detection unavailable. Enter the number of PDF pages.';
      $('page-mode').textContent = 'Enter pages';
    } finally {
      reading = false;
      updateQuote();
    }
  }
  pdfInput.addEventListener('change', processPDF);
  const drop = $('upload-drop');
  ['dragenter','dragover'].forEach((event) => drop.addEventListener(event, (e) => {e.preventDefault();drop.classList.add('drag-over');}));
  ['dragleave','drop'].forEach((event) => drop.addEventListener(event, (e) => {e.preventDefault();drop.classList.remove('drag-over');}));
  drop.addEventListener('drop',(event) => {
    const files=event.dataTransfer?.files;
    if (files?.length) { pdfInput.files=files;pdfInput.dispatchEvent(new Event('change')); }
  });
  form.addEventListener('submit', (event) => {
    if (submit.disabled) {event.preventDefault();return;}
    if (!window.confirm('Submit this print request with the selected settings?')) {event.preventDefault();return;}
    submit.disabled = true;
    submit.textContent = 'Submitting request…';
  });
  updateQuote();
})();
