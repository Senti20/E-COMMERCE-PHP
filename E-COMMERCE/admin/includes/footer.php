<?php
?>
            </div>
            
            <footer class="admin-footer">
                <div class="container-fluid px-4">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> ECCOMERCE. All rights reserved.</p>
                </div>
            </footer>
        </div>
    </div> 
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    $(document).ready(function() {
        $('.datatable').DataTable({
            pageLength: 10,
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });
        
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 3000);
    });
    
    function confirmLogout() {
        if (confirm("Are you sure you want to logout?")) {
            window.location.href = "../auth/logout";
        }
    }
    </script>
</body>
</html>