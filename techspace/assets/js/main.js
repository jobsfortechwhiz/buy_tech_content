// ─── Auto-dismiss flash messages ───────────────────────────────────────────
const flash = document.getElementById('flash-msg');
if (flash) setTimeout(() => flash.style.opacity = '0', 4000);

// ─── Drop Zone ─────────────────────────────────────────────────────────────
const dropZone    = document.getElementById('dropZone');
const fileInput   = document.getElementById('document');
const dropSelected= document.getElementById('dropSelected');
const titleInput  = document.getElementById('title');

function showFile(file) {
  if (!file) return;
  dropSelected.textContent = '📎 ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
  // Auto-fill title from filename if empty
  if (titleInput && !titleInput.value.trim()) {
    titleInput.value = file.name.replace(/\.[^.]+$/, '').replace(/[-_]/g, ' ');
  }
}

if (fileInput) {
  fileInput.addEventListener('change', () => showFile(fileInput.files[0]));
}

if (dropZone) {
  ['dragover','dragenter'].forEach(ev => {
    dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
  });
  ['dragleave','drop'].forEach(ev => {
    dropZone.addEventListener(ev, () => dropZone.classList.remove('drag-over'));
  });
  dropZone.addEventListener('drop', e => {
    e.preventDefault();
    const file = e.dataTransfer.files[0];
    if (file && fileInput) {
      const dt = new DataTransfer();
      dt.items.add(file);
      fileInput.files = dt.files;
      showFile(file);
    }
  });
}

// ─── Upload button loading state ───────────────────────────────────────────
const uploadForm  = document.getElementById('uploadForm');
const submitBtn   = document.getElementById('submitBtn');

if (uploadForm && submitBtn) {
  uploadForm.addEventListener('submit', () => {
    submitBtn.textContent = '⏳ Uploading…';
    submitBtn.disabled = true;
  });
}

// ─── Scroll-reveal cards ───────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const targets = document.querySelectorAll('.doc-card, .stat-card, .upload-card');
  if (!('IntersectionObserver' in window)) return;
  const io = new IntersectionObserver(entries => {
    entries.forEach(el => {
      if (el.isIntersecting) {
        el.target.style.opacity = '1';
        el.target.style.transform = 'translateY(0)';
        io.unobserve(el.target);
      }
    });
  }, { threshold: 0.1 });

  targets.forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(16px)';
    el.style.transition = 'opacity .35s ease, transform .35s ease';
    io.observe(el);
  });
});
