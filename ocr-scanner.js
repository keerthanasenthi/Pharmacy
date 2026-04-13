/**
 * OCR capture from camera/photo using Tesseract.js (client-side).
 * - Shows modal with image preview + extracted raw text
 * - Suggests: medicineName, quantity, mfgDate, expDate
 * - Integrates by calling: openOcrScanner({ onApply: (suggestions, rawText) => {} })
 */
(function (global) {
  'use strict';

  var overlayEl = null;
  var inputEl = null;
  var previewImgEl = null;
  var progressEl = null;
  var textEl = null;
  var sugNameEl = null;
  var sugQtyEl = null;
  var sugMfgEl = null;
  var sugExpEl = null;
  var currentOnApply = null;
  var working = false;

  function ensureModal() {
    if (overlayEl) return;

    overlayEl = document.createElement('div');
    overlayEl.id = 'ocr-modal';
    overlayEl.className = 'ocr-overlay';
    overlayEl.innerHTML =
      '<div class="ocr-box">' +
      '  <div class="ocr-header">' +
      '    <h3>Capture Photo (OCR)</h3>' +
      '    <button type="button" class="ocr-close" aria-label="Close">&times;</button>' +
      '  </div>' +
      '  <div>' +
      '    <input type="file" id="ocr-file" accept="image/*" capture="environment" />' +
      '    <span style="font-size:0.85rem;color:#555;margin-left:8px;">Take a clear photo of the package text (Name, MFG, EXP, Qty)</span>' +
      '  </div>' +
      '  <div class="ocr-grid" style="margin-top:12px;">' +
      '    <div>' +
      '      <div class="ocr-preview"><span style="color:#777;font-size:0.9rem;">No image yet</span></div>' +
      '      <div class="ocr-progress" id="ocr-progress"></div>' +
      '    </div>' +
      '    <div>' +
      '      <textarea class="ocr-textarea" id="ocr-text" placeholder="Extracted text will appear here..." readonly></textarea>' +
      '      <div class="ocr-suggestions">' +
      '        <div><label>Medicine name (suggested)</label><input type="text" id="ocr-sug-name" /></div>' +
      '        <div><label>Quantity (suggested)</label><input type="text" id="ocr-sug-qty" /></div>' +
      '        <div><label>MFG date (suggested)</label><input type="text" id="ocr-sug-mfg" placeholder="YYYY-MM-DD or MM/YY" /></div>' +
      '        <div><label>EXP date (suggested)</label><input type="text" id="ocr-sug-exp" placeholder="YYYY-MM-DD or MM/YY" /></div>' +
      '      </div>' +
      '      <div class="ocr-actions">' +
      '        <button type="button" class="btn-ocr-cancel">Cancel</button>' +
      '        <button type="button" class="btn-ocr-apply">Apply</button>' +
      '      </div>' +
      '    </div>' +
      '  </div>' +
      '</div>';

    document.body.appendChild(overlayEl);

    inputEl = overlayEl.querySelector('#ocr-file');
    previewImgEl = overlayEl.querySelector('.ocr-preview');
    progressEl = overlayEl.querySelector('#ocr-progress');
    textEl = overlayEl.querySelector('#ocr-text');
    sugNameEl = overlayEl.querySelector('#ocr-sug-name');
    sugQtyEl = overlayEl.querySelector('#ocr-sug-qty');
    sugMfgEl = overlayEl.querySelector('#ocr-sug-mfg');
    sugExpEl = overlayEl.querySelector('#ocr-sug-exp');

    overlayEl.querySelector('.ocr-close').onclick = closeOcrScanner;
    overlayEl.querySelector('.btn-ocr-cancel').onclick = closeOcrScanner;
    overlayEl.onclick = function (e) {
      if (e.target === overlayEl) closeOcrScanner();
    };

    overlayEl.querySelector('.btn-ocr-apply').onclick = function () {
      if (working) return;
      var suggestions = {
        medicineName: (sugNameEl.value || '').trim(),
        quantity: (sugQtyEl.value || '').trim(),
        mfgDate: (sugMfgEl.value || '').trim(),
        expDate: (sugExpEl.value || '').trim()
      };
      var raw = (textEl.value || '').trim();
      // Save callback before closing (close resets state)
      var cb = currentOnApply;
      closeOcrScanner();
      if (typeof cb === 'function') cb(suggestions, raw);
    };

    inputEl.onchange = function () {
      if (!inputEl.files || !inputEl.files[0]) return;
      runOcr(inputEl.files[0]);
    };
  }

  function closeOcrScanner() {
    if (!overlayEl) return;
    overlayEl.classList.remove('ocr-open');
    currentOnApply = null;
    working = false;
    if (inputEl) inputEl.value = '';
  }

  function setProgress(msg) {
    if (progressEl) progressEl.textContent = msg || '';
  }

  function setPreview(url) {
    previewImgEl.innerHTML = '';
    var img = document.createElement('img');
    img.src = url;
    previewImgEl.appendChild(img);
  }

  function normalizeText(raw) {
    return (raw || '')
      .replace(/\r/g, '\n')
      .replace(/[ \t]+/g, ' ')
      .replace(/\n{3,}/g, '\n\n')
      .trim();
  }

  function extractQuantity(text) {
    // Examples: "Qty: 10", "Quantity 15", "Net Qty 100ml", "10 Tablets", "15 Caps"
    var t = text.toUpperCase();
    var m =
      t.match(/\b(QTY|QUANTITY|NET QTY|NET)\s*[:\-]?\s*([0-9]{1,4})\s*([A-Z]{0,6})\b/) ||
      t.match(/\b([0-9]{1,4})\s*(TABLETS?|TABS?|CAPSULES?|CAPS?|ML|MG|G)\b/);
    if (!m) return '';
    if (m[2]) return (m[2] + (m[3] ? ' ' + m[3] : '')).trim();
    return (m[1] + ' ' + m[2]).trim();
  }

  function extractDateNearKeyword(text, keyword) {
    // Recognize: 2026-03-06, 06/03/2026, 03/2026, 03-26, MAR 2026
    var t = text.toUpperCase();
    var idx = t.indexOf(keyword);
    if (idx < 0) return '';
    var slice = t.slice(idx, idx + 80);

    var m =
      slice.match(/(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})/) || // yyyy-mm-dd
      slice.match(/(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})/) || // dd-mm-yyyy
      slice.match(/(\d{1,2})[\/\-.](\d{2,4})/) || // mm/yy or mm/yyyy
      slice.match(/\b(JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|SEPT|OCT|NOV|DEC)[A-Z]*\s+(\d{2,4})\b/);

    if (!m) return '';
    return m[0].trim();
  }

  function suggestMedicineName(text) {
    // Best-effort: pick the first "clean" line with letters, ignoring common noise.
    var lines = normalizeText(text).split('\n').map(function (l) { return l.trim(); }).filter(Boolean);
    var bad = /(MFG|MANUFACT|EXP|EXPIR|BATCH|MRP|RS\.|PRICE|DOSAGE|TABLET|CAPSULE|SYRUP|NET|QTY|GST|HOSPITAL|SCHEDULE)/i;
    for (var i = 0; i < lines.length; i++) {
      var line = lines[i];
      if (line.length < 4) continue;
      if (bad.test(line)) continue;
      if (!/[A-Z]/i.test(line)) continue;
      // avoid lines that are mostly numbers/symbols
      var letters = (line.match(/[A-Z]/gi) || []).length;
      if (letters < 4) continue;
      return line.replace(/\s{2,}/g, ' ').trim();
    }
    return '';
  }

  async function preprocessToBlob(file) {
    // Light preprocessing to help OCR: grayscale + contrast + threshold.
    var url = URL.createObjectURL(file);
    var img = new Image();
    img.src = url;
    await img.decode();

    var canvas = document.createElement('canvas');
    var maxW = 1400;
    var scale = Math.min(1, maxW / img.width);
    canvas.width = Math.floor(img.width * scale);
    canvas.height = Math.floor(img.height * scale);
    var ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

    var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    var data = imageData.data;

    for (var i = 0; i < data.length; i += 4) {
      var r = data[i], g = data[i + 1], b = data[i + 2];
      var gray = (0.299 * r + 0.587 * g + 0.114 * b);
      // increase contrast
      var c = (gray - 128) * 1.3 + 128;
      // simple threshold
      var v = c > 160 ? 255 : 0;
      data[i] = data[i + 1] = data[i + 2] = v;
    }
    ctx.putImageData(imageData, 0, 0);

    URL.revokeObjectURL(url);
    return new Promise(function (resolve) {
      canvas.toBlob(function (blob) { resolve(blob); }, 'image/png', 1.0);
    });
  }

  async function runOcr(file) {
    ensureModal();
    working = true;
    setProgress('Preparing image...');
    textEl.value = '';
    sugNameEl.value = '';
    sugQtyEl.value = '';
    sugMfgEl.value = '';
    sugExpEl.value = '';

    var previewUrl = URL.createObjectURL(file);
    setPreview(previewUrl);

    if (typeof Tesseract === 'undefined') {
      setProgress('OCR library not loaded. Please refresh.');
      working = false;
      return;
    }

    var blob = await preprocessToBlob(file);

    setProgress('Reading text...');
    try {
      var result = await Tesseract.recognize(blob, 'eng', {
        logger: function (m) {
          if (m && m.status) {
            var pct = m.progress ? Math.round(m.progress * 100) : null;
            setProgress(m.status + (pct !== null ? ' (' + pct + '%)' : ''));
          }
        }
      });

      var rawText = (result && result.data && result.data.text) ? result.data.text : '';
      var cleaned = normalizeText(rawText);
      textEl.value = cleaned;

      // suggestions
      sugNameEl.value = suggestMedicineName(cleaned);
      sugQtyEl.value = extractQuantity(cleaned);
      sugMfgEl.value = extractDateNearKeyword(cleaned, 'MFG') || extractDateNearKeyword(cleaned, 'MFD') || '';
      sugExpEl.value = extractDateNearKeyword(cleaned, 'EXP') || extractDateNearKeyword(cleaned, 'EXPIRY') || extractDateNearKeyword(cleaned, 'BEST BEFORE') || '';

      setProgress('Done. Review suggestions and click Apply.');
    } catch (e) {
      setProgress('OCR failed. Try a clearer photo (good light, focused).');
    } finally {
      working = false;
      try { URL.revokeObjectURL(previewUrl); } catch (e2) {}
    }
  }

  function openOcrScanner(opts) {
    ensureModal();
    currentOnApply = opts && opts.onApply ? opts.onApply : null;
    overlayEl.classList.add('ocr-open');
    setProgress('');
  }

  global.openOcrScanner = openOcrScanner;
  global.closeOcrScanner = closeOcrScanner;
})(typeof window !== 'undefined' ? window : this);

