<?php
/**
 * Copyright 2026 RIZAPATIID - PROJECT
 * Email : rizapatiid@gmail.com
 */
?>
            <!-- Dashboard Footer -->
            <footer class="mt-auto py-3 text-center text-muted" style="border-top: 1px solid #e2e8f0; font-size: 0.9rem;">
                &copy; <?php echo date('Y'); ?> <strong>RIZA PATIID - PROJECT</strong>. All rights reserved.
            </footer>
        </div> <!-- End of main-content -->
    </div> <!-- End of main-wrapper -->

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- DataTables Buttons -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom Init Scripts -->
    <script>
        $(document).ready(function() {
            // Initialize DataTables with Export Buttons
            if ($('.datatable').length) {
                $('.datatable').DataTable({
                    dom: "<'row mb-3'<'col-md-6'l><'col-md-6'f>>" +
                         "<'row'<'col-sm-12'tr>>" +
                         "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json',
                        search: "",
                        searchPlaceholder: "Cari data..."
                    }
                });
            }

            // SweetAlert Delete Confirmation
            $('.btn-delete').on('click', function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });

            // Show Session Flash Messages (if any)
            <?php if (isset($_SESSION['success'])): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: '<?php echo $_SESSION['success']; ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: '<?php echo $_SESSION['error']; ?>',
                });
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['warning'])): ?>
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian!',
                    text: '<?php echo $_SESSION['warning']; ?>',
                    confirmButtonColor: '#0f172a'
                });
                <?php unset($_SESSION['warning']); ?>
            <?php endif; ?>

            <?php if (isset($is_default_password) && $is_default_password): ?>
                Swal.fire({
                    title: 'Ganti Password Default',
                    text: 'Demi keamanan, Anda wajib mengganti password default (12345) sebelum melanjutkan.',
                    icon: 'security',
                    html: `
                        <div class="text-start mb-3">
                            <label class="small fw-bold text-muted">Password Baru</label>
                            <input type="password" id="swal-new-pass" class="form-control" placeholder="Min. 5 karakter">
                        </div>
                        <div class="text-start">
                            <label class="small fw-bold text-muted">Konfirmasi Password Baru</label>
                            <input type="password" id="swal-conf-pass" class="form-control" placeholder="Ulangi password baru">
                        </div>
                    `,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    confirmButtonText: 'Simpan Password Baru',
                    confirmButtonColor: '#0f172a',
                    preConfirm: () => {
                        const newPass = document.getElementById('swal-new-pass').value;
                        const confPass = document.getElementById('swal-conf-pass').value;
                        if (!newPass || newPass.length < 5) {
                            Swal.showValidationMessage('Password minimal 5 karakter!');
                            return false;
                        }
                        if (newPass !== confPass) {
                            Swal.showValidationMessage('Konfirmasi password tidak cocok!');
                            return false;
                        }
                        return { newPass: newPass };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '../pages/profil.php';
                        
                        const input1 = document.createElement('input');
                        input1.type = 'hidden';
                        input1.name = 'new_password';
                        input1.value = result.value.newPass;
                        form.appendChild(input1);
                        
                        const input2 = document.createElement('input');
                        input2.type = 'hidden';
                        input2.name = 'confirm_password';
                        input2.value = result.value.newPass;
                        form.appendChild(input2);
                        
                        const input3 = document.createElement('input');
                        input3.type = 'hidden';
                        input3.name = 'change_password_direct';
                        input3.value = '1';
                        form.appendChild(input3);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>
