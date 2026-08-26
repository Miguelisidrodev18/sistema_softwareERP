  </div><!-- /.content-wrapper -->
</div><!-- /#main-content -->

<!-- Modal de confirmacion global -->
<div class="modal fade" id="confirmModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:380px">
    <div class="modal-content">
      <div class="modal-header" style="background:#dc3545">
        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirmar accion</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p id="confirmMessage" class="mb-0">¿Esta seguro de realizar esta accion?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="confirmBtn">Confirmar</button>
      </div>
    </div>
  </div>
</div>

<!-- Toast container -->
<div id="toast-container"></div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= baseUrl('assets/js/main.js') ?>"></script>
<?php if (isset($extraJs)) echo $extraJs; ?>
</body>
</html>
