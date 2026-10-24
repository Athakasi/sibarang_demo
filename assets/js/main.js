/* ===================================================
   JavaScript Validasi Form - form-validation.js
   =================================================== */

// Validasi form barang
function validateBarangForm() {
    const nama = document.getElementById('nama_barang')?.value.trim();
    const stok = document.getElementById('stok')?.value;
    
    if (!nama) {
        alert('Nama barang harus diisi!');
        return false;
    }
    
    if (!stok || stok < 0) {
        alert('Stok harus berupa angka positif!');
        return false;
    }
    
    return true;
}

// Validasi form barang masuk/keluar
function validateTransactionForm() {
    const tanggal = document.getElementById('tanggal')?.value;
    const barang = document.getElementById('nama_barang')?.value;
    const jumlah = document.getElementById('jumlah')?.value;
    
    if (!tanggal) {
        alert('Tanggal harus diisi!');
        return false;
    }
    
    if (!barang) {
        alert('Nama barang harus dipilih!');
        return false;
    }
    
    if (!jumlah || jumlah <= 0) {
        alert('Jumlah harus lebih dari 0!');
        return false;
    }
    
    return true;
}

// Format angka dengan pemisah ribuan
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// Real-time calculation
function calculateStok() {
    const stokAwal = parseInt(document.getElementById('stok_awal')?.value) || 0;
    const masuk = parseInt(document.getElementById('jumlah_masuk')?.value) || 0;
    const keluar = parseInt(document.getElementById('jumlah_keluar')?.value) || 0;
    
    const stokAkhir = stokAwal + masuk - keluar;
    
    const display = document.getElementById('stok_akhir_display');
    if (display) {
        display.textContent = formatNumber(stokAkhir);
        display.style.color = stokAkhir < 0 ? '#e74c3c' : '#27ae60';
    }
    
    return stokAkhir;
}

// Konfirmasi hapus
function confirmDelete(itemName) {
    return confirm(`Apakah Anda yakin ingin menghapus ${itemName}?`);
}

// Toast notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#27ae60' : type === 'error' ? '#e74c3c' : '#3498db'};
        color: white;
        border-radius: 5px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
