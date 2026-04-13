/**
 * Smart Pharmacy - Barcode scanner via camera
 * Uses html5-qrcode (supports barcodes: EAN, CODE_128, etc.)
 */
(function (global) {
  'use strict';

  var scannerInstance = null;
  var scannerEl = null;

  function getScannerElement() {
    if (scannerEl) return scannerEl;
    scannerEl = document.getElementById('barcode-scanner-modal');
    if (!scannerEl) {
      scannerEl = document.createElement('div');
      scannerEl.id = 'barcode-scanner-modal';
      scannerEl.className = 'barcode-scanner-overlay';
      scannerEl.innerHTML =
        '<div class="barcode-scanner-box">' +
        '  <div class="barcode-scanner-header">' +
        '    <h3>Scan Barcode</h3>' +
        '    <button type="button" class="barcode-scanner-close" aria-label="Close">&times;</button>' +
        '  </div>' +
        '  <div id="barcode-scanner-reader"></div>' +
        '  <p class="barcode-scanner-hint">Point camera at barcode on medicine package</p>' +
        '</div>';
      document.body.appendChild(scannerEl);

      scannerEl.querySelector('.barcode-scanner-close').onclick = function () {
        closeBarcodeScanner();
      };
      scannerEl.onclick = function (e) {
        if (e.target === scannerEl) closeBarcodeScanner();
      };
    }
    return scannerEl;
  }

  function closeBarcodeScanner() {
    if (scannerInstance) {
      try {
        scannerInstance.stop().catch(function () {});
      } catch (e) {}
      scannerInstance = null;
    }
    var el = document.getElementById('barcode-scanner-modal');
    if (el) {
      el.classList.remove('barcode-scanner-open');
      var reader = document.getElementById('barcode-scanner-reader');
      if (reader) reader.innerHTML = '';
    }
  }

  function openBarcodeScanner(onScan) {
    if (typeof Html5Qrcode === 'undefined') {
      alert('Scanner library not loaded. Please refresh the page.');
      return;
    }

    var overlay = getScannerElement();
    var readerDiv = document.getElementById('barcode-scanner-reader');
    if (!readerDiv) return;
    readerDiv.innerHTML = '';

    overlay.classList.add('barcode-scanner-open');

    // Important: many medicine packs have QR / DataMatrix, not just 1D barcodes.
    // Keep a square-ish scan box for QR, but still works for barcodes.
    var config = {
      fps: 12,
      qrbox: function (viewfinderWidth, viewfinderHeight) {
        var minEdge = Math.min(viewfinderWidth, viewfinderHeight);
        var size = Math.max(220, Math.floor(minEdge * 0.7));
        return { width: size, height: size };
      }
    };

    // If formats enum is available, explicitly support both QR + common barcodes.
    // If not available, don't restrict formats (library will try all supported formats).
    if (typeof Html5QrcodeSupportedFormats !== 'undefined') {
      config.formatsToSupport = [
        Html5QrcodeSupportedFormats.QR_CODE,
        Html5QrcodeSupportedFormats.DATA_MATRIX,
        Html5QrcodeSupportedFormats.CODE_128,
        Html5QrcodeSupportedFormats.EAN_13,
        Html5QrcodeSupportedFormats.EAN_8,
        Html5QrcodeSupportedFormats.UPC_A,
        Html5QrcodeSupportedFormats.UPC_E,
        Html5QrcodeSupportedFormats.CODE_39
      ];
    }

    scannerInstance = new Html5Qrcode('barcode-scanner-reader');

    scannerInstance
      .start(
        { facingMode: 'environment' },
        config,
        function (decodedText) {
          closeBarcodeScanner();
          if (typeof onScan === 'function') onScan(decodedText.trim());
        },
        function () {}
      )
      .catch(function (err) {
        overlay.classList.remove('barcode-scanner-open');
        alert('Camera error: ' + (err.message || 'Could not access camera. Allow camera permission and try again.'));
      });
  }

  global.openBarcodeScanner = openBarcodeScanner;
  global.closeBarcodeScanner = closeBarcodeScanner;
})(typeof window !== 'undefined' ? window : this);
