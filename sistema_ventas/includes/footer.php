</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cerrar automáticamente las alertas después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var alertas = document.querySelectorAll('.alert');
                alertas.forEach(function(alerta) {
                    var bsAlert = new bootstrap.Alert(alerta);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>