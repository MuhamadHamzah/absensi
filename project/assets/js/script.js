// Main JavaScript for Employee Attendance System

document.addEventListener('DOMContentLoaded', function() {
    // Initialize current time display for the clock
    initClock();
    
    // Toggle sidebar on mobile
    const menuToggle = document.getElementById('menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            const content = document.querySelector('.content');
            
            sidebar.classList.toggle('sidebar-collapsed');
            content.classList.toggle('content-expanded');
        });
    }
    
    // Initialize charts if on dashboard page
    if (document.querySelector('#attendance-chart')) {
        initCharts();
    }
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        });
    });
    
    // Data tables
    if (document.querySelector('.data-table')) {
        initDataTables();
    }
    
    // Initialize tooltips
    initTooltips();
});

// Initialize clock display
function initClock() {
    const clockElement = document.getElementById('current-time');
    if (clockElement) {
        updateClock();
        setInterval(updateClock, 1000);
    }
}

// Update clock time
function updateClock() {
    const clockElement = document.getElementById('current-time');
    const now = new Date();
    
    let hours = now.getHours();
    let minutes = now.getMinutes();
    let seconds = now.getSeconds();
    
    // Add leading zeros
    hours = hours < 10 ? '0' + hours : hours;
    minutes = minutes < 10 ? '0' + minutes : minutes;
    seconds = seconds < 10 ? '0' + seconds : seconds;
    
    const timeString = `${hours}:${minutes}:${seconds}`;
    clockElement.textContent = timeString;
}

// Initialize charts for dashboard
function initCharts() {
    // Attendance overview chart (using Chart.js)
    const attendanceCtx = document.getElementById('attendance-chart').getContext('2d');
    const attendanceChart = new Chart(attendanceCtx, {
        type: 'bar',
        data: {
            labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            datasets: [{
                label: 'Present',
                data: [25, 28, 27, 26, 24, 10, 5],
                backgroundColor: '#10B981',
                borderWidth: 0
            }, {
                label: 'Late',
                data: [3, 2, 4, 5, 6, 2, 1],
                backgroundColor: '#F59E0B',
                borderWidth: 0
            }, {
                label: 'Absent',
                data: [2, 0, 1, 1, 3, 0, 0],
                backgroundColor: '#EF4444',
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: true
                },
                y: {
                    stacked: true,
                    beginAtZero: true
                }
            }
        }
    });
    
    // Department attendance chart
    const deptCtx = document.getElementById('department-chart').getContext('2d');
    const deptChart = new Chart(deptCtx, {
        type: 'doughnut',
        data: {
            labels: ['IT', 'HR', 'Marketing', 'Finance', 'Operations'],
            datasets: [{
                data: [12, 8, 10, 9, 15],
                backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EC4899', '#8B5CF6'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Initialize data tables
function initDataTables() {
    const tables = document.querySelectorAll('.data-table');
    tables.forEach(table => {
        new DataTable(table, {
            pageLength: 10,
            responsive: true
        });
    });
}

// Initialize tooltips
function initTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(tooltipTriggerEl => {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Confirm delete
function confirmDelete(id, type) {
    if (confirm(`Are you sure you want to delete this ${type}?`)) {
        document.getElementById(`delete-form-${id}`).submit();
    }
}

// Filter attendance records
function filterAttendance() {
    const month = document.getElementById('filter-month').value;
    const year = document.getElementById('filter-year').value;
    const department = document.getElementById('filter-department').value;
    
    window.location.href = `index.php?page=attendance&month=${month}&year=${year}&department=${department}`;
}